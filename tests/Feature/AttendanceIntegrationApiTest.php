<?php

use App\Models\Attendance;
use App\Models\Company;
use App\Models\Employee;
use App\Models\IntegrationAttendanceEvent;
use App\Models\IntegrationClient;
use App\Models\User;
use Illuminate\Support\Facades\Config;

function signedAttendanceIntegrationHeaders(string $body, string $apiKey, string $secret, int|string|null $timestamp = null): array
{
    $timestamp ??= time();

    return [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_ACCEPT' => 'application/json',
        'HTTP_X_HRCONNECT_API_KEY' => $apiKey,
        'HTTP_X_HRCONNECT_TIMESTAMP' => (string) $timestamp,
        'HTTP_X_HRCONNECT_SIGNATURE' => 'sha256='.hash_hmac('sha256', $timestamp.'.'.$body, $secret),
    ];
}

beforeEach(function (): void {
    Config::set('services.attendance_integration.api_key', 'integration-test-key');
    Config::set('services.attendance_integration.secret', 'integration-test-secret');
    Config::set('services.attendance_integration.signature_tolerance_seconds', 300);
    Config::set('services.attendance_integration.allowed_sources', []);

    // integration_attendance_events.integration_client_id NOT NULL — semua event
    // harus terikat IntegrationClient (bukan fallback config).
    [$this->client, $this->clientApiKey, $this->clientSecret] = IntegrationClient::issue([
        'company_id' => Company::factory()->create()->id,
        'name' => 'Integration Test Client',
        'abilities' => [IntegrationClient::ABILITY_ATTENDANCE_WRITE],
    ]);
});

test('attendance integration endpoint requires an api key before signature validation', function () {
    $payload = [
        'source' => 'solution',
        'idempotency_key' => 'evt-unauthorized',
        'employee_code' => 'EMP-001',
        'event_type' => 'check_in',
        'occurred_at' => '2026-05-13 08:00:00',
    ];

    $this->postJson('/api/v1/integrations/attendance-events', $payload)
        ->assertUnauthorized();
});

test('attendance integration endpoint rejects invalid api key', function () {
    $payload = [
        'source' => 'solution',
        'idempotency_key' => 'evt-invalid-key',
        'employee_code' => 'EMP-001',
        'event_type' => 'check_in',
        'occurred_at' => '2026-05-13 08:00:00',
    ];
    $body = json_encode($payload, JSON_THROW_ON_ERROR);

    $this->call('POST', '/api/v1/integrations/attendance-events', [], [], [], signedAttendanceIntegrationHeaders($body, 'wrong-key', $this->clientSecret), $body)
        ->assertUnauthorized()
        ->assertJsonPath('message', 'Invalid integration API key.');
});

test('attendance integration endpoint requires a valid hmac signature after api key passes', function () {
    $payload = [
        'source' => 'solution',
        'idempotency_key' => 'evt-invalid-signature',
        'employee_code' => 'EMP-001',
        'event_type' => 'check_in',
        'occurred_at' => '2026-05-13 08:00:00',
    ];

    $this->postJson('/api/v1/integrations/attendance-events', $payload, [
        'X-HRConnect-Api-Key' => $this->clientApiKey,
    ])->assertUnauthorized();
});

test('attendance integration can restrict allowed sources', function () {
    [$restricted, $restrictedKey, $restrictedSecret] = IntegrationClient::issue([
        'company_id' => Company::factory()->create()->id,
        'name' => 'Source Restricted Client',
        'abilities' => [IntegrationClient::ABILITY_ATTENDANCE_WRITE],
        'allowed_sources' => ['solution'],
    ]);

    $payload = [
        'source' => 'unknown-gateway',
        'idempotency_key' => 'evt-invalid-source',
        'employee_code' => 'EMP-001',
        'event_type' => 'check_in',
        'occurred_at' => '2026-05-13 08:00:00',
    ];
    $body = json_encode($payload, JSON_THROW_ON_ERROR);

    $this->call('POST', '/api/v1/integrations/attendance-events', [], [], [], signedAttendanceIntegrationHeaders($body, $restrictedKey, $restrictedSecret), $body)
        ->assertUnprocessable()
        ->assertJsonValidationErrors('source');
});

test('attendance integration event maps employee code and records check in', function () {
    $user = User::factory()->create(['group' => 'user']);
    $employee = Employee::factory()->create([
        'user_id' => $user->id,
        'employee_number' => 'EMP-001',
    ]);

    $payload = [
        'source' => 'solution',
        'idempotency_key' => 'solution-1001',
        'employee_code' => 'EMP-001',
        'event_type' => 'clock_in',
        'occurred_at' => '2026-05-13 08:03:00',
        'device_id' => 'machine-a',
        'latitude' => -6.2,
        'longitude' => 106.8,
        'payload' => ['pin' => '001'],
    ];
    $body = json_encode($payload, JSON_THROW_ON_ERROR);

    $this->call('POST', '/api/v1/integrations/attendance-events', [], [], [], signedAttendanceIntegrationHeaders($body, $this->clientApiKey, $this->clientSecret), $body)
        ->assertAccepted()
        ->assertJsonPath('success', true)
        ->assertJsonPath('status', IntegrationAttendanceEvent::STATUS_PROCESSED);

    $attendance = Attendance::query()->where('employee_id', $employee->id)->firstOrFail();

    expect($attendance->date->toDateString())->toBe('2026-05-13')
        ->and($attendance->time_in?->format('H:i:s'))->toBe('08:03:00')
        ->and((float) $attendance->latitude_in)->toBe(-6.2)
        ->and((float) $attendance->longitude_in)->toBe(106.8);

    $this->assertDatabaseHas('integration_attendance_events', [
        'source' => 'solution',
        'idempotency_key' => 'solution-1001',
        'employee_code' => 'EMP-001',
        'user_id' => $user->id,
        'attendance_id' => $attendance->id,
        'integration_client_id' => $this->client->id,
        'status' => IntegrationAttendanceEvent::STATUS_PROCESSED,
    ]);
});

test('attendance integration idempotency key prevents duplicate replay mutation', function () {
    $user = User::factory()->create(['group' => 'user']);
    $employee = Employee::factory()->create([
        'user_id' => $user->id,
        'employee_number' => 'EMP-002',
    ]);

    $payload = [
        'source' => 'sbg',
        'idempotency_key' => 'sbg-replay-1',
        'employee_code' => 'EMP-002',
        'event_type' => 'check_in',
        'occurred_at' => '2026-05-13 07:55:00',
    ];
    $body = json_encode($payload, JSON_THROW_ON_ERROR);

    $first = $this->call('POST', '/api/v1/integrations/attendance-events', [], [], [], signedAttendanceIntegrationHeaders($body, $this->clientApiKey, $this->clientSecret), $body)
        ->assertAccepted()
        ->json('event_id');

    $payload['occurred_at'] = '2026-05-13 09:30:00';
    $replayBody = json_encode($payload, JSON_THROW_ON_ERROR);

    $this->call('POST', '/api/v1/integrations/attendance-events', [], [], [], signedAttendanceIntegrationHeaders($replayBody, $this->clientApiKey, $this->clientSecret), $replayBody)
        ->assertAccepted()
        ->assertJsonPath('event_id', $first)
        ->assertJsonPath('status', IntegrationAttendanceEvent::STATUS_PROCESSED);

    expect(IntegrationAttendanceEvent::query()->count())->toBe(1)
        ->and(Attendance::query()->where('employee_id', $employee->id)->count())->toBe(1)
        ->and(Attendance::query()->where('employee_id', $employee->id)->first()?->time_in?->format('H:i:s'))->toBe('07:55:00');
});

test('attendance integration stores failed event when employee code is unknown', function () {
    $payload = [
        'source' => 'solution',
        'idempotency_key' => 'missing-employee',
        'employee_code' => 'UNKNOWN',
        'event_type' => 'check_in',
        'occurred_at' => '2026-05-13 08:00:00',
    ];
    $body = json_encode($payload, JSON_THROW_ON_ERROR);

    $this->call('POST', '/api/v1/integrations/attendance-events', [], [], [], signedAttendanceIntegrationHeaders($body, $this->clientApiKey, $this->clientSecret), $body)
        ->assertUnprocessable()
        ->assertJsonPath('success', false)
        ->assertJsonPath('status', IntegrationAttendanceEvent::STATUS_FAILED);

    $this->assertDatabaseHas('integration_attendance_events', [
        'idempotency_key' => 'missing-employee',
        'status' => IntegrationAttendanceEvent::STATUS_FAILED,
        'integration_client_id' => $this->client->id,
    ]);
});

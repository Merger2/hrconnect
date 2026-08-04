<?php

use App\Models\Attendance;
use App\Models\IntegrationAttendanceEvent;
use App\Models\User;
use Illuminate\Support\Facades\Config;

function signedAttendanceIntegrationHeaders(string $body, int|string|null $timestamp = null): array
{
    $timestamp ??= time();
    $secret = 'integration-test-secret';

    return [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_ACCEPT' => 'application/json',
        'HTTP_X_PASPAPAN_API_KEY' => 'integration-test-key',
        'HTTP_X_PASPAPAN_TIMESTAMP' => (string) $timestamp,
        'HTTP_X_PASPAPAN_SIGNATURE' => 'sha256='.hash_hmac('sha256', $timestamp.'.'.$body, $secret),
    ];
}

beforeEach(function (): void {
    Config::set('services.attendance_integration.api_key', 'integration-test-key');
    Config::set('services.attendance_integration.secret', 'integration-test-secret');
    Config::set('services.attendance_integration.signature_tolerance_seconds', 300);
    Config::set('services.attendance_integration.allowed_sources', []);
});

test('attendance integration endpoint requires an api key before signature validation', function () {
    $payload = [
        'source' => 'solution',
        'idempotency_key' => 'evt-unauthorized',
        'employee_code' => 'EMP-001',
        'event_type' => 'check_in',
        'occurred_at' => '2026-05-13 08:00:00',
    ];

    $this->postJson('/api/integrations/attendance-events', $payload)
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
    $headers = signedAttendanceIntegrationHeaders($body);
    $headers['HTTP_X_PASPAPAN_API_KEY'] = 'wrong-key';

    $this->call('POST', '/api/integrations/attendance-events', [], [], [], $headers, $body)
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

    $this->postJson('/api/integrations/attendance-events', $payload, [
        'X-PasPapan-Api-Key' => 'integration-test-key',
    ])->assertUnauthorized();
});

test('attendance integration can restrict allowed sources', function () {
    Config::set('services.attendance_integration.allowed_sources', ['solution']);

    $payload = [
        'source' => 'unknown-gateway',
        'idempotency_key' => 'evt-invalid-source',
        'employee_code' => 'EMP-001',
        'event_type' => 'check_in',
        'occurred_at' => '2026-05-13 08:00:00',
    ];
    $body = json_encode($payload, JSON_THROW_ON_ERROR);

    $this->call('POST', '/api/integrations/attendance-events', [], [], [], signedAttendanceIntegrationHeaders($body), $body)
        ->assertUnprocessable()
        ->assertJsonValidationErrors('source');
});

test('attendance integration event maps employee code and records check in', function () {
    $user = User::factory()->create([
        'group' => 'user',
        'nip' => 'EMP-001',
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

    $this->call('POST', '/api/integrations/attendance-events', [], [], [], signedAttendanceIntegrationHeaders($body), $body)
        ->assertAccepted()
        ->assertJsonPath('success', true)
        ->assertJsonPath('status', IntegrationAttendanceEvent::STATUS_PROCESSED);

    $attendance = Attendance::query()->where('user_id', $user->id)->firstOrFail();

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
        'status' => IntegrationAttendanceEvent::STATUS_PROCESSED,
    ]);
});

test('attendance integration idempotency key prevents duplicate replay mutation', function () {
    $user = User::factory()->create([
        'group' => 'user',
        'nip' => 'EMP-002',
    ]);

    $payload = [
        'source' => 'sbg',
        'idempotency_key' => 'sbg-replay-1',
        'employee_code' => 'EMP-002',
        'event_type' => 'check_in',
        'occurred_at' => '2026-05-13 07:55:00',
    ];
    $body = json_encode($payload, JSON_THROW_ON_ERROR);

    $first = $this->call('POST', '/api/integrations/attendance-events', [], [], [], signedAttendanceIntegrationHeaders($body), $body)
        ->assertAccepted()
        ->json('event_id');

    $payload['occurred_at'] = '2026-05-13 09:30:00';
    $replayBody = json_encode($payload, JSON_THROW_ON_ERROR);

    $this->call('POST', '/api/integrations/attendance-events', [], [], [], signedAttendanceIntegrationHeaders($replayBody), $replayBody)
        ->assertAccepted()
        ->assertJsonPath('event_id', $first)
        ->assertJsonPath('status', IntegrationAttendanceEvent::STATUS_PROCESSED);

    expect(IntegrationAttendanceEvent::query()->count())->toBe(1)
        ->and(Attendance::query()->where('user_id', $user->id)->count())->toBe(1)
        ->and(Attendance::query()->where('user_id', $user->id)->first()?->time_in?->format('H:i:s'))->toBe('07:55:00');
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

    $this->call('POST', '/api/integrations/attendance-events', [], [], [], signedAttendanceIntegrationHeaders($body), $body)
        ->assertUnprocessable()
        ->assertJsonPath('success', false)
        ->assertJsonPath('status', IntegrationAttendanceEvent::STATUS_FAILED);

    $this->assertDatabaseHas('integration_attendance_events', [
        'idempotency_key' => 'missing-employee',
        'status' => IntegrationAttendanceEvent::STATUS_FAILED,
    ]);
});

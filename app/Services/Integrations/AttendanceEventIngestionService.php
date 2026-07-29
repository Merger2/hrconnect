<?php

declare(strict_types=1);

namespace App\Services\Integrations;

use App\Models\ActivityLog;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\IntegrationAttendanceEvent;
use App\Models\IntegrationClient;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AttendanceEventIngestionService
{
    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $rawPayload
     */
    public function ingest(array $payload, array $rawPayload, ?IntegrationClient $client = null): IntegrationAttendanceEvent
    {
        $normalized = $this->normalize($payload);

        return DB::transaction(function () use ($normalized, $rawPayload, $client): IntegrationAttendanceEvent {
            $event = IntegrationAttendanceEvent::query()
                ->where('source', $normalized['source'])
                ->where('idempotency_key', $normalized['idempotency_key'])
                ->lockForUpdate()
                ->first();

            if ($event instanceof IntegrationAttendanceEvent) {
                return $event;
            }

            $event = IntegrationAttendanceEvent::query()->create([
                ...$normalized,
                'integration_client_id' => $client?->id,
                'status' => IntegrationAttendanceEvent::STATUS_ACCEPTED,
                'normalized_payload' => $normalized,
                'raw_payload' => $rawPayload,
            ]);

            return $this->process($event);
        });
    }

    private function process(IntegrationAttendanceEvent $event): IntegrationAttendanceEvent
    {
        $employee = Employee::query()
            ->where('employee_number', $event->employee_code)
            ->first();

        if (! $employee instanceof Employee) {
            return $this->fail($event, __('Employee code was not found.'));
        }

        $occurredAt = $event->occurred_at instanceof Carbon ? $event->occurred_at : Carbon::parse($event->occurred_at);

        $attendance = Attendance::query()->firstOrNew([
            'employee_id' => $employee->id,
            'date' => $occurredAt->toDateString(),
        ]);

        if ($event->event_type === IntegrationAttendanceEvent::EVENT_CHECK_IN) {
            if ($attendance->clock_in === null) {
                $attendance->fill([
                    'clock_in' => $occurredAt,
                    'lat_in' => $event->latitude,
                    'long_in' => $event->longitude,
                    'status' => $attendance->status === 'absent' ? 'present' : ($attendance->status ?: 'present'),
                ]);
            }
        } elseif ($attendance->clock_out === null) {
            $attendance->fill([
                'clock_out' => $occurredAt,
                'lat_out' => $event->latitude,
                'long_out' => $event->longitude,
                'status' => $attendance->status === 'absent' ? 'present' : ($attendance->status ?: 'present'),
            ]);
        }

        $attendance->save();

        $event->forceFill([
            'user_id' => $employee->user_id,
            'attendance_id' => $attendance->id,
            'status' => IntegrationAttendanceEvent::STATUS_PROCESSED,
            'error_message' => null,
            'processed_at' => now(),
        ])->save();

        ActivityLog::record(
            'Attendance Integration Event',
            __(':source attendance event :key processed for :employee.', [
                'source' => $event->source,
                'key' => $event->idempotency_key,
                'employee' => $employee->full_name ?? $employee->user?->name,
            ]),
        );

        return $event;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function normalize(array $payload): array
    {
        $eventType = match ((string) $payload['event_type']) {
            'clock_in', 'in' => IntegrationAttendanceEvent::EVENT_CHECK_IN,
            'clock_out', 'out' => IntegrationAttendanceEvent::EVENT_CHECK_OUT,
            default => (string) $payload['event_type'],
        };

        return [
            'source' => Str::of((string) ($payload['source'] ?? 'generic'))->lower()->limit(80, '')->toString(),
            'idempotency_key' => (string) $payload['idempotency_key'],
            'employee_code' => trim((string) $payload['employee_code']),
            'event_type' => $eventType,
            'occurred_at' => Carbon::parse((string) $payload['occurred_at']),
            'latitude' => array_key_exists('latitude', $payload) ? $payload['latitude'] : null,
            'longitude' => array_key_exists('longitude', $payload) ? $payload['longitude'] : null,
            'device_id' => $payload['device_id'] ?? null,
        ];
    }

    private function fail(IntegrationAttendanceEvent $event, string $message): IntegrationAttendanceEvent
    {
        $event->forceFill([
            'status' => IntegrationAttendanceEvent::STATUS_FAILED,
            'error_message' => $message,
            'processed_at' => now(),
        ])->save();

        return $event;
    }
}

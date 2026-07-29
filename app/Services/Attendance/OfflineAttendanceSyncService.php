<?php

declare(strict_types=1);

namespace App\Services\Attendance;

use App\Models\Attendance;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OfflineAttendanceSyncService
{
    public function sync(User $user, array $items): array
    {
        $employee = $user->employee;

        if (! $employee) {
            Log::warning('Offline sync skipped: no employee record', ['user_id' => $user->id]);

            return [];
        }

        return DB::transaction(function () use ($employee, $items): array {
            $results = [];

            foreach ($items as $item) {
                $occurredAt = CarbonImmutable::parse($item['occurred_at']);
                $date = $occurredAt->toDateString();

                if ($item['event_type'] === 'clock_in') {
                    $attendance = Attendance::firstOrNew([
                        'employee_id' => $employee->id,
                        'date' => $date,
                    ]);

                    $attendance->fill([
                        'clock_in' => $occurredAt,
                        'lat_in' => $item['latitude'] ?? null,
                        'long_in' => $item['longitude'] ?? null,
                        'clock_in_accuracy' => $item['accuracy'] ?? null,
                        'device_fingerprint' => $item['device_id'] ?? null,
                        'verification_method' => 'offline',
                    ]);

                    if ($attendance->exists && $attendance->isDirty()) {
                        $attendance->save();
                    } elseif (! $attendance->exists) {
                        $attendance->save();
                    }
                } elseif ($item['event_type'] === 'clock_out') {
                    $attendance = Attendance::where('employee_id', $employee->id)
                        ->whereDate('date', $date)
                        ->whereNotNull('clock_in')
                        ->latest()
                        ->first();

                    if ($attendance) {
                        $attendance->fill([
                            'clock_out' => $occurredAt,
                            'lat_out' => $item['latitude'] ?? null,
                            'long_out' => $item['longitude'] ?? null,
                            'clock_out_accuracy' => $item['accuracy'] ?? null,
                            'clock_out_verification_method' => 'offline',
                        ]);

                        if ($attendance->isDirty()) {
                            $attendance->save();
                        }
                    } else {
                        // Clock-out without clock-in: create standalone record
                        $attendance = Attendance::create([
                            'employee_id' => $employee->id,
                            'date' => $date,
                            'clock_in' => null,
                            'clock_out' => $occurredAt,
                            'lat_out' => $item['latitude'] ?? null,
                            'long_out' => $item['longitude'] ?? null,
                            'clock_out_accuracy' => $item['accuracy'] ?? null,
                            'device_fingerprint' => $item['device_id'] ?? null,
                            'clock_out_verification_method' => 'offline',
                            'status' => 'absent',
                        ]);
                    }
                }

                $results[] = [
                    'id' => $attendance->id,
                    'event_type' => $item['event_type'],
                    'occurred_at' => $item['occurred_at'],
                    'status' => 'synced',
                ];
            }

            Log::info('Offline attendance synced', [
                'employee_id' => $employee->id,
                'count' => count($items),
            ]);

            return $results;
        });
    }
}

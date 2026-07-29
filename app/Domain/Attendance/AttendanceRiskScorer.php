<?php

declare(strict_types=1);

namespace App\Domain\Attendance;

use App\Enums\AttendanceStatus;
use App\Models\Attendance;
use App\Models\Shift;
use Carbon\Carbon;

class AttendanceRiskScorer
{
    public function score(
        Attendance $attendance,
        ?Shift $shift,
        string $event,
        array $context = [],
    ): array {
        $factors = [];

        $gpsAccuracy = $context['gps_accuracy'] ?? null;
        $gpsVariance = $context['gps_variance'] ?? null;
        $distance = $context['distance'] ?? null;
        $radius = $context['radius'] ?? null;
        $faceConfidence = $context['face_confidence'] ?? null;
        $qrTokenRetries = (int) ($context['qr_token_retries'] ?? 0);

        if (($context['face_verification_failed'] ?? false) === true || ($context['face_verification_skipped'] ?? false) === true) {
            $faceConfidence = 0.0;
        }

        $metadata = [
            'source' => $context['source'] ?? null,
            'platform' => $context['platform'] ?? null,
            'device_id' => $context['device_id'] ?? null,
        ];

        if (($context['mock_location_detected'] ?? false) === true) {
            $factors[] = $this->factor('mock_location_detected', 100, $event, $metadata);
        }

        if (($context['offline_submitted'] ?? false) === true) {
            $factors[] = $this->factor('offline_submitted', 40, $event, $metadata);
        }

        if (($context['cached_location'] ?? false) === true) {
            $factors[] = $this->factor('cached_location_used', 15, $event, $metadata);
        }

        if ($gpsAccuracy !== null && (float) $gpsAccuracy < 5.0) {
            $factors[] = $this->factor('gps_accuracy_too_perfect', 20, $event, [
                ...$metadata,
                'accuracy' => (float) $gpsAccuracy,
            ]);
        }

        if ($gpsVariance !== null && (float) $gpsVariance == 0.0) {
            $factors[] = $this->factor('gps_zero_variance', 20, $event, $metadata);
        }

        if ($distance !== null && $radius !== null && $radius > 0) {
            $distance = (float) $distance;
            $radius = (float) $radius;

            if ($distance >= ($radius * 0.85) && $distance <= $radius) {
                $factors[] = $this->factor('near_attendance_radius', 15, $event, [
                    ...$metadata,
                    'distance' => $distance,
                    'radius' => $radius,
                ]);
            }
        }

        if (($context['device_changed'] ?? false) === true) {
            $factors[] = $this->factor('device_changed', 15, $event, $metadata);
        }

        if (($context['device_info_missing'] ?? false) === true) {
            $factors[] = $this->factor('device_info_missing', 10, $event, [
                'source' => $context['source'] ?? null,
                'platform' => $context['platform'] ?? null,
            ]);
        }

        if ($faceConfidence !== null && (float) $faceConfidence < 0.65) {
            $factors[] = $this->factor('face_confidence_low', 20, $event, [
                ...$metadata,
                'confidence' => (float) $faceConfidence,
            ]);
        }

        if ($qrTokenRetries > 0) {
            $factors[] = $this->factor('qr_token_retry', min(25, 10 * $qrTokenRetries), $event, [
                ...$metadata,
                'retries' => $qrTokenRetries,
            ]);
        }

        $timestampFactor = $this->timestampRiskFactor($context, $event, $metadata);

        if ($timestampFactor !== null) {
            $factors[] = $timestampFactor;
        }

        if ($event === 'check_in' && $shift !== null && $attendance->clock_in !== null) {
            $factors = array_merge($factors, $this->timeRiskFactors($attendance, $shift, $event));
        }

        $score = min(100, array_sum(array_column($factors, 'score')));

        return [
            'score' => $score,
            'level' => $this->level($score),
            'factors' => $factors,
            'evaluated_at' => now(),
        ];
    }

    private function timestampRiskFactor(array $context, string $event, array $metadata): ?array
    {
        if (! isset($context['captured_at'])) {
            return null;
        }

        try {
            $capturedAt = Carbon::parse($context['captured_at']);
            $receivedAt = isset($context['received_at'])
                ? Carbon::parse($context['received_at'])
                : now();
        } catch (\Throwable) {
            return $this->factor('timestamp_invalid', 15, $event, $metadata);
        }

        if ($capturedAt->greaterThan($receivedAt->copy()->addMinutes(5))) {
            return $this->factor('timestamp_in_future', 20, $event, [
                ...$metadata,
                'captured_at' => $capturedAt->toDateTimeString(),
                'received_at' => $receivedAt->toDateTimeString(),
            ]);
        }

        $ageMinutes = abs($capturedAt->diffInMinutes($receivedAt, false));
        $threshold = ($context['offline_submitted'] ?? false) === true ? 1440 : 15;

        if ($ageMinutes > $threshold) {
            return $this->factor('timestamp_anomaly', 15, $event, [
                ...$metadata,
                'captured_at' => $capturedAt->toDateTimeString(),
                'received_at' => $receivedAt->toDateTimeString(),
                'age_minutes' => $ageMinutes,
            ]);
        }

        return null;
    }

    public function merge(?array $existingFactors, int $existingScore, array $newRisk): array
    {
        $factors = array_values(array_merge($existingFactors ?? [], $newRisk['factors'] ?? []));
        $score = min(100, $existingScore + (int) ($newRisk['score'] ?? 0));

        return [
            'score' => $score,
            'level' => $this->level($score),
            'factors' => $factors,
            'evaluated_at' => $newRisk['evaluated_at'] ?? now(),
        ];
    }

    public function level(int $score): string
    {
        return match (true) {
            $score >= 60 => 'high',
            $score >= 25 => 'medium',
            default => 'low',
        };
    }

    private function timeRiskFactors(Attendance $attendance, Shift $shift, string $event): array
    {
        $clockIn = Carbon::parse($attendance->clock_in);
        $shiftStart = Carbon::parse($shift->start_time)->setDate($clockIn->year, $clockIn->month, $clockIn->day);

        if ($clockIn->lt($shiftStart->copy()->subMinutes(120))) {
            return [$this->factor('check_in_too_early', 10, $event, [
                'shift_start' => $shiftStart->toDateTimeString(),
                'clock_in' => $clockIn->toDateTimeString(),
            ])];
        }

        if ($attendance->status === AttendanceStatus::LATE) {
            return [$this->factor('check_in_late', 10, $event, [
                'shift_start' => $shiftStart->toDateTimeString(),
                'clock_in' => $clockIn->toDateTimeString(),
            ])];
        }

        return [];
    }

    private function factor(string $code, int $score, string $event, array $context = []): array
    {
        return [
            'code' => $code,
            'event' => $event,
            'score' => $score,
            'context' => array_filter(
                $context,
                fn ($value): bool => $value !== null && $value !== '',
            ),
        ];
    }
}

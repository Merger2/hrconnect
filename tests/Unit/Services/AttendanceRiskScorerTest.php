<?php

use App\Enums\AttendanceStatus;
use App\Models\Attendance;
use App\Models\Shift;
use App\Services\AttendanceRiskScorer;

function mockAttendance(array $attributes = []): Attendance
{
    $mock = Mockery::mock(Attendance::class)->makePartial();
    foreach ($attributes as $key => $value) {
        $mock->setAttribute($key, $value);
    }

    return $mock;
}

function mockShift(string $startTime = '08:00', string $endTime = '17:00'): Shift
{
    $mock = Mockery::mock(Shift::class)->makePartial();
    $mock->start_time = $startTime;
    $mock->end_time = $endTime;

    return $mock;
}

describe('AttendanceRiskScorer', function () {
    it('returns low risk with no risk factors', function () {
        $attendance = Mockery::mock(Attendance::class);
        $scorer = new AttendanceRiskScorer;
        $result = $scorer->score($attendance, null, 'check_in', []);

        expect($result['score'])->toBe(0);
        expect($result['level'])->toBe('low');
        expect($result['factors'])->toBe([]);
    });

    it('flags mock location as 100 risk', function () {
        $scorer = new AttendanceRiskScorer;
        $result = $scorer->score(
            Mockery::mock(Attendance::class), null, 'check_in',
            ['mock_location_detected' => true]
        );

        expect($result['score'])->toBe(100);
        expect($result['level'])->toBe('high');
        expect($result['factors'])->toHaveCount(1);
        expect($result['factors'][0]['code'])->toBe('mock_location_detected');
    });

    it('flags offline submission as 40 risk', function () {
        $scorer = new AttendanceRiskScorer;
        $result = $scorer->score(
            Mockery::mock(Attendance::class), null, 'check_in',
            ['offline_submitted' => true]
        );

        expect($result['score'])->toBe(40);
        expect($result['level'])->toBe('medium');
    });

    it('flags cached location as 15 risk', function () {
        $scorer = new AttendanceRiskScorer;
        $result = $scorer->score(
            Mockery::mock(Attendance::class), null, 'check_in',
            ['cached_location' => true]
        );

        expect($result['score'])->toBe(15);
        expect($result['level'])->toBe('low');
    });

    it('flags gps accuracy too perfect as 20 risk', function () {
        $scorer = new AttendanceRiskScorer;
        $result = $scorer->score(
            Mockery::mock(Attendance::class), null, 'check_in',
            ['gps_accuracy' => 3.0]
        );

        expect($result['score'])->toBe(20);
    });

    it('does not flag gps accuracy above 5', function () {
        $scorer = new AttendanceRiskScorer;
        $result = $scorer->score(
            Mockery::mock(Attendance::class), null, 'check_in',
            ['gps_accuracy' => 10.0]
        );

        expect($result['score'])->toBe(0);
    });

    it('flags zero gps variance as 20 risk', function () {
        $scorer = new AttendanceRiskScorer;
        $result = $scorer->score(
            Mockery::mock(Attendance::class), null, 'check_in',
            ['gps_variance' => 0.0]
        );

        expect($result['score'])->toBe(20);
    });

    it('flags near radius edge as 15 risk', function () {
        $scorer = new AttendanceRiskScorer;
        $result = $scorer->score(
            Mockery::mock(Attendance::class), null, 'check_in',
            ['distance' => 90.0, 'radius' => 100.0]
        );

        expect($result['score'])->toBe(15);
    });

    it('does not flag distance well within radius', function () {
        $scorer = new AttendanceRiskScorer;
        $result = $scorer->score(
            Mockery::mock(Attendance::class), null, 'check_in',
            ['distance' => 10.0, 'radius' => 100.0]
        );

        expect($result['score'])->toBe(0);
    });

    it('flags device changed as 15 risk', function () {
        $scorer = new AttendanceRiskScorer;
        $result = $scorer->score(
            Mockery::mock(Attendance::class), null, 'check_in',
            ['device_changed' => true]
        );

        expect($result['score'])->toBe(15);
    });

    it('flags missing device info as 10 risk', function () {
        $scorer = new AttendanceRiskScorer;
        $result = $scorer->score(
            Mockery::mock(Attendance::class), null, 'check_in',
            ['device_info_missing' => true]
        );

        expect($result['score'])->toBe(10);
    });

    it('flags low face confidence as 20 risk', function () {
        $scorer = new AttendanceRiskScorer;
        $result = $scorer->score(
            Mockery::mock(Attendance::class), null, 'check_in',
            ['face_confidence' => 0.5]
        );

        expect($result['score'])->toBe(20);
    });

    it('does not flag high face confidence', function () {
        $scorer = new AttendanceRiskScorer;
        $result = $scorer->score(
            Mockery::mock(Attendance::class), null, 'check_in',
            ['face_confidence' => 0.9]
        );

        expect($result['score'])->toBe(0);
    });

    it('sets face confidence to 0 when verification failed', function () {
        $scorer = new AttendanceRiskScorer;
        $result = $scorer->score(
            Mockery::mock(Attendance::class), null, 'check_in',
            ['face_verification_failed' => true, 'face_confidence' => 0.9]
        );

        expect($result['score'])->toBe(20);
    });

    it('sets face confidence to 0 when verification skipped', function () {
        $scorer = new AttendanceRiskScorer;
        $result = $scorer->score(
            Mockery::mock(Attendance::class), null, 'check_in',
            ['face_verification_skipped' => true, 'face_confidence' => 0.9]
        );

        expect($result['score'])->toBe(20);
    });

    it('flags QR token retries increasing score', function () {
        $scorer = new AttendanceRiskScorer;
        $result = $scorer->score(
            Mockery::mock(Attendance::class), null, 'check_in',
            ['qr_token_retries' => 3]
        );

        expect($result['score'])->toBe(25);
    });

    it('caps QR retry score at 25 for high retries', function () {
        $scorer = new AttendanceRiskScorer;
        $result = $scorer->score(
            Mockery::mock(Attendance::class), null, 'check_in',
            ['qr_token_retries' => 10]
        );

        expect($result['score'])->toBe(25);
    });

    it('caps total score at 100', function () {
        $scorer = new AttendanceRiskScorer;
        $result = $scorer->score(
            Mockery::mock(Attendance::class), null, 'check_in',
            [
                'mock_location_detected' => true,
                'offline_submitted' => true,
                'cached_location' => true,
            ]
        );

        expect($result['score'])->toBe(100);
    });

    it('detects invalid timestamp', function () {
        $scorer = new AttendanceRiskScorer;
        $result = $scorer->score(
            Mockery::mock(Attendance::class), null, 'check_in',
            ['captured_at' => 'not-a-date']
        );

        expect($result['score'])->toBe(15);
    });

    it('detects future timestamp', function () {
        $scorer = new AttendanceRiskScorer;
        $result = $scorer->score(
            Mockery::mock(Attendance::class), null, 'check_in',
            ['captured_at' => now()->addHour()->toDateTimeString()]
        );

        expect($result['score'])->toBe(20);
    });

    it('detects old timestamp anomaly', function () {
        $scorer = new AttendanceRiskScorer;
        $result = $scorer->score(
            Mockery::mock(Attendance::class), null, 'check_in',
            ['captured_at' => now()->subHours(2)->toDateTimeString()]
        );

        expect($result['score'])->toBe(15);
    });

    it('allows offline timestamp up to 24h', function () {
        $scorer = new AttendanceRiskScorer;
        $result = $scorer->score(
            Mockery::mock(Attendance::class), null, 'check_in',
            [
                'captured_at' => now()->subHours(12)->toDateTimeString(),
                'offline_submitted' => true,
            ]
        );

        expect($result['score'])->toBe(40); // only offline flag
    });

    it('flags check-in too early (before shift - 2h)', function () {
        $attendance = mockAttendance(['clock_in' => now()->setTime(5, 0)->toDateTimeString()]);
        $shift = mockShift('08:00');

        $scorer = new AttendanceRiskScorer;
        $result = $scorer->score($attendance, $shift, 'check_in', []);

        expect($result['score'])->toBe(10);
        expect($result['factors'][0]['code'])->toBe('check_in_too_early');
    });

    it('flags late check-in', function () {
        $attendance = mockAttendance([
            'clock_in' => now()->setTime(9, 0)->toDateTimeString(),
            'status' => AttendanceStatus::LATE,
        ]);
        $shift = mockShift('08:00');

        $scorer = new AttendanceRiskScorer;
        $result = $scorer->score($attendance, $shift, 'check_in', []);

        expect($result['score'])->toBe(10);
        expect($result['factors'][0]['code'])->toBe('check_in_late');
    });

    it('does not apply time risk for non-check_in events', function () {
        $attendance = mockAttendance(['clock_in' => now()->setTime(5, 0)->toDateTimeString()]);
        $shift = mockShift('08:00');

        $scorer = new AttendanceRiskScorer;
        $result = $scorer->score($attendance, $shift, 'check_out', []);

        expect($result['score'])->toBe(0);
    });

    describe('level()', function () {
        it('returns low for score < 25', function () {
            $scorer = new AttendanceRiskScorer;
            expect($scorer->level(0))->toBe('low');
            expect($scorer->level(24))->toBe('low');
        });

        it('returns medium for score 25-59', function () {
            $scorer = new AttendanceRiskScorer;
            expect($scorer->level(25))->toBe('medium');
            expect($scorer->level(40))->toBe('medium');
            expect($scorer->level(59))->toBe('medium');
        });

        it('returns high for score >= 60', function () {
            $scorer = new AttendanceRiskScorer;
            expect($scorer->level(60))->toBe('high');
            expect($scorer->level(100))->toBe('high');
        });
    });

    describe('merge()', function () {
        it('merges existing factors with new risk', function () {
            $scorer = new AttendanceRiskScorer;
            $existing = $scorer->score(Mockery::mock(Attendance::class), null, 'check_in', ['cached_location' => true]);
            $newRisk = $scorer->score(Mockery::mock(Attendance::class), null, 'check_in', ['device_changed' => true]);

            $merged = $scorer->merge($existing['factors'], $existing['score'], $newRisk);

            expect($merged['score'])->toBe(30);
            expect($merged['factors'])->toHaveCount(2);
        });

        it('handles null existing factors', function () {
            $scorer = new AttendanceRiskScorer;
            $newRisk = $scorer->score(Mockery::mock(Attendance::class), null, 'check_in', ['device_changed' => true]);

            $merged = $scorer->merge(null, 0, $newRisk);

            expect($merged['score'])->toBe(15);
            expect($merged['factors'])->toHaveCount(1);
        });

        it('caps merged score at 100', function () {
            $scorer = new AttendanceRiskScorer;
            $newRisk = $scorer->score(Mockery::mock(Attendance::class), null, 'check_in', ['mock_location_detected' => true]);

            $merged = $scorer->merge([], 50, $newRisk);

            expect($merged['score'])->toBe(100);
        });
    });
});

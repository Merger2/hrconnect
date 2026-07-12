<?php

use App\Enums\ApprovalStatus;
use App\Enums\AttendanceStatus;
use App\Enums\VerificationMethod;
use App\Exceptions\AlreadyClockedInException;
use App\Exceptions\AntiFakeGPSException;
use App\Exceptions\BusinessRuleException;
use App\Exceptions\NotClockedInException;
use App\Models\Attendance;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\Shift;
use App\Models\User;
use App\Services\AttendanceRiskScorer;
use App\Services\AttendanceService;
use App\Services\FaceRecognitionService;
use App\Services\GeofenceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

// ─── Helpers ─────────────────────────────────────────────────────────────

function createTestEmployee(): Employee
{
    $company = Company::factory()->create();
    $branch = Branch::factory()->for($company)->create([
        'latitude' => -6.2088,
        'longitude' => 106.8456,
        'radius' => 100,
    ]);
    $department = Department::factory()->for($branch)->create();
    $position = Position::factory()->for($department)->create();
    $shift = Shift::factory()->create();
    $user = User::factory()->create();

    $employee = Employee::factory()->create([
        'user_id' => $user->id,
        'company_id' => $company->id,
        'branch_id' => $branch->id,
        'department_id' => $department->id,
        'position_id' => $position->id,
        'shift_id' => $shift->id,
    ]);

    $employee->forceFill(['pin' => '123456'])->save();

    return $employee;
}

function createActiveAttendance(Employee $employee): Attendance
{
    $id = DB::table('attendances')->insertGetId([
        'employee_id' => $employee->id,
        'shift_id' => $employee->shift_id,
        'date' => now()->toDateString(),
        'clock_in' => now()->subHours(5),
        'verification_method' => VerificationMethod::PIN_VERIFIED->value,
        'status' => AttendanceStatus::ON_TIME->value,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return Attendance::find($id);
}

// ─── clockIn() ───────────────────────────────────────────────────────────

describe('clockIn', function () {
    it('throws AntiFakeGPSException when is_mocked is true', function () {
        $employee = createTestEmployee();

        $faceService = mock(FaceRecognitionService::class);
        $faceService->shouldReceive('hasFaceEnrolled')
            ->zeroOrMoreTimes()
            ->andReturn(false);

        $service = new AttendanceService(
            mock(GeofenceService::class),
            $faceService,
            new AttendanceRiskScorer,
        );

        expect(fn () => $service->clockIn($employee, ['is_mocked' => true]))
            ->toThrow(AntiFakeGPSException::class, 'Fake GPS');
    });

    it('throws AlreadyClockedInException if employee already clocked in today', function () {
        $employee = createTestEmployee();
        createActiveAttendance($employee);

        $geofence = mock(GeofenceService::class);
        $geofence->shouldReceive('validateLocation')
            ->zeroOrMoreTimes()
            ->andReturn(['valid' => true, 'distance' => 10]);

        $faceService = mock(FaceRecognitionService::class);
        $faceService->shouldReceive('hasFaceEnrolled')
            ->zeroOrMoreTimes()
            ->andReturn(false);

        $service = new AttendanceService(
            $geofence,
            $faceService,
            new AttendanceRiskScorer,
        );

        expect(fn () => $service->clockIn($employee, [
            'latitude' => -6.2088,
            'longitude' => 106.8456,
            'pin' => '123456',
        ]))->toThrow(AlreadyClockedInException::class);
    });

    it('throws BusinessRuleException for WFA with short note (< 20 chars)', function () {
        $employee = createTestEmployee();

        $faceService = mock(FaceRecognitionService::class);
        $faceService->shouldReceive('hasFaceEnrolled')
            ->zeroOrMoreTimes()
            ->andReturn(false);

        $service = new AttendanceService(
            mock(GeofenceService::class),
            $faceService,
            new AttendanceRiskScorer,
        );

        expect(fn () => $service->clockIn($employee, [
            'is_wfa' => true,
            'wfa_note' => 'Short',
            'pin' => '123456',
        ]))->toThrow(BusinessRuleException::class, 'minimal 20 karakter');
    });

    it('creates attendance successfully in WFA mode with valid note', function () {
        $employee = createTestEmployee();

        $faceService = mock(FaceRecognitionService::class);
        $faceService->shouldReceive('hasFaceEnrolled')
            ->zeroOrMoreTimes()
            ->andReturn(false);

        $service = new AttendanceService(
            mock(GeofenceService::class),
            $faceService,
            new AttendanceRiskScorer,
        );

        $result = $service->clockIn($employee, [
            'is_wfa' => true,
            'wfa_note' => 'Saya bekerja dari rumah hari ini karena banjir di area sekitar.',
            'pin' => '123456',
        ]);

        expect($result)->toBeInstanceOf(Attendance::class);
        expect($result->employee_id)->toBe($employee->id);
        expect($result->is_wfa)->toBeTrue();
        expect($result->status_wfa)->toBe(ApprovalStatus::PENDING);
        expect($result->wfa_note)->toBe('Saya bekerja dari rumah hari ini karena banjir di area sekitar.');
        expect($result->verification_method)->toBe(VerificationMethod::PIN_VERIFIED);
        expect($result->clock_in)->not->toBeNull();
        expect($result->lat_in)->toBeNull();
        expect($result->long_in)->toBeNull();
        expect($result->late_minutes)->toBe(0);
    });

    it('throws BusinessRuleException for non-WFA without branch', function () {
        $employee = createTestEmployee();
        // Unset the loaded relationship to simulate no branch
        $employee->setRelation('branch', null);

        $faceService = mock(FaceRecognitionService::class);
        $faceService->shouldReceive('hasFaceEnrolled')
            ->zeroOrMoreTimes()
            ->andReturn(false);

        $service = new AttendanceService(
            mock(GeofenceService::class),
            $faceService,
            new AttendanceRiskScorer,
        );

        expect(fn () => $service->clockIn($employee, [
            'latitude' => -6.2088,
            'longitude' => 106.8456,
            'pin' => '123456',
        ]))->toThrow(BusinessRuleException::class, 'Data lokasi kerja');
    });

    it('allows WFA clock-in without branch when note is valid', function () {
        $employee = createTestEmployee();
        $employee->setRelation('branch', null);

        $geofence = mock(GeofenceService::class);
        $geofence->shouldNotReceive('validateLocation');

        $faceService = mock(FaceRecognitionService::class);
        $faceService->shouldReceive('hasFaceEnrolled')
            ->zeroOrMoreTimes()
            ->andReturn(false);

        $service = new AttendanceService(
            $geofence,
            $faceService,
            new AttendanceRiskScorer,
        );

        $result = $service->clockIn($employee, [
            'is_wfa' => true,
            'wfa_note' => 'Saya bekerja dari rumah karena perlu fokus menyelesaikan laporan.',
            'pin' => '123456',
        ]);

        expect($result->is_wfa)->toBeTrue();
        expect($result->status_wfa)->toBe(ApprovalStatus::PENDING);
        expect($result->late_minutes)->toBe(0);
    });

    it('creates attendance successfully with face verification and all fields', function () {
        $employee = createTestEmployee();

        // Bypass pgvector cast — set face_embedding directly in attributes
        $ref = new ReflectionClass($employee);
        $prop = $ref->getProperty('attributes');
        $attrs = $prop->getValue($employee);
        $attrs['face_embedding'] = '['.implode(',', array_fill(0, 128, 0.1)).']';
        $prop->setValue($employee, $attrs);

        $geofence = mock(GeofenceService::class);
        $geofence->shouldReceive('validateLocation')
            ->once()
            ->andReturn(['valid' => true, 'distance' => 10]);

        $faceService = mock(FaceRecognitionService::class);
        $faceService->shouldReceive('hasFaceEnrolled')
            ->zeroOrMoreTimes()
            ->andReturn(true);
        $faceService->shouldReceive('verifyFace')
            ->once()
            ->andReturn(['valid' => true, 'similarity_percentage' => 95.0]);

        $service = new AttendanceService($geofence, $faceService, new AttendanceRiskScorer);

        $result = $service->clockIn($employee, [
            'latitude' => -6.2088,
            'longitude' => 106.8456,
            'accuracy' => 10,
            'face_embedding' => array_fill(0, 128, 0.1),
            'photo_selfie' => 'selfie-base64-data',
        ]);

        expect($result)->toBeInstanceOf(Attendance::class);
        expect($result->employee_id)->toBe($employee->id);
        expect($result->clock_in)->not->toBeNull();
        expect($result->lat_in)->toEqual(-6.2088);
        expect($result->long_in)->toEqual(106.8456);
        expect($result->clock_in_accuracy)->toEqual(10.0);
        expect($result->photo_selfie_in)->toBe('selfie-base64-data');
        expect($result->is_wfa)->toBeFalse();
        expect($result->verification_method)->toBe(VerificationMethod::FACE_VERIFIED);
        expect($result->face_similarity_score)->toEqual(95.0);
        expect($result->late_minutes)->toBeInt();
    });
});

// ─── clockOut() ──────────────────────────────────────────────────────────

describe('clockOut', function () {
    it('throws AntiFakeGPSException when is_mocked is true', function () {
        $employee = createTestEmployee();

        $faceService = mock(FaceRecognitionService::class);
        $faceService->shouldReceive('hasFaceEnrolled')
            ->zeroOrMoreTimes()
            ->andReturn(false);

        $service = new AttendanceService(
            mock(GeofenceService::class),
            $faceService,
            new AttendanceRiskScorer,
        );

        expect(fn () => $service->clockOut($employee, ['is_mocked' => true]))
            ->toThrow(AntiFakeGPSException::class, 'Fake GPS');
    });

    it('throws NotClockedInException when no active attendance exists', function () {
        $employee = createTestEmployee();

        $faceService = mock(FaceRecognitionService::class);
        $faceService->shouldReceive('hasFaceEnrolled')
            ->zeroOrMoreTimes()
            ->andReturn(false);

        $service = new AttendanceService(
            mock(GeofenceService::class),
            $faceService,
            new AttendanceRiskScorer,
        );

        expect(fn () => $service->clockOut($employee, [
            'latitude' => -6.2088,
            'longitude' => 106.8456,
            'pin' => '123456',
        ]))->toThrow(NotClockedInException::class);
    });

    it('updates attendance record on successful clock-out', function () {
        $employee = createTestEmployee();
        $attendance = createActiveAttendance($employee);

        $geofence = mock(GeofenceService::class);
        $geofence->shouldReceive('validateLocation')
            ->once()
            ->andReturn(['valid' => true, 'distance' => 10]);

        $faceService = mock(FaceRecognitionService::class);
        $faceService->shouldReceive('hasFaceEnrolled')
            ->zeroOrMoreTimes()
            ->andReturn(false);

        $service = new AttendanceService(
            $geofence,
            $faceService,
            new AttendanceRiskScorer,
        );

        $result = $service->clockOut($employee, [
            'latitude' => -6.2088,
            'longitude' => 106.8456,
            'accuracy' => 12,
            'pin' => '123456',
            'photo_selfie' => 'selfie-out-data',
        ]);

        expect($result->id)->toBe($attendance->id);
        expect($result->clock_out)->not->toBeNull();
        expect($result->lat_out)->toEqual(-6.2088);
        expect($result->long_out)->toEqual(106.8456);
        expect($result->clock_out_accuracy)->toEqual(12.0);
        expect($result->photo_selfie_out)->toBe('selfie-out-data');
        expect($result->clock_out_verification_method)->toBe(VerificationMethod::PIN_VERIFIED);
        expect($result->clock_out_face_similarity_score)->toBeNull();
    });
});

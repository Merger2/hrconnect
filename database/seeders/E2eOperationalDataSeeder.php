<?php

namespace Database\Seeders;

use App\Enums\BloodType;
use App\Enums\EducationLevel;
use App\Enums\EmployeeStatus;
use App\Enums\EmploymentType;
use App\Enums\Gender;
use App\Enums\MaritalStatus;
use App\Enums\ReimbursementStatus;
use App\Enums\RequestStatus;
use App\Enums\SalaryType;
use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use App\Models\CashAdvance;
use App\Models\Company;
use App\Models\Division;
use App\Models\Employee;
use App\Models\EmployeeDocumentRequest;
use App\Models\EmployeeDocumentTemplate;
use App\Models\Leave;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\Overtime;
use App\Models\Position;
use App\Models\Reimbursement;
use App\Models\ReimbursementCategory;
use App\Models\Schedule;
use App\Models\Shift;
use App\Models\ShiftSwapRequest;
use App\Models\User;
use App\Models\WorkFromHomeRequest;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * E2eOperationalDataSeeder — complete study case data untuk E2E testing.
 *
 * Structure:
 * - employee@hrconnect.test  → EMP-E2E-001 (IT Staff, reports to manager)
 * - manager@hrconnect.test   → EMP-E2E-004 (OPS Manager, has 2 subordinates)
 * - finance@hrconnect.test   → EMP-E2E-005 (FIN Staff, reports to manager)
 * - hr@hrconnect.test        → EMP-E2E-002 (HR Manager, has 3 subordinates)
 * - admin@hrconnect.local    → Super Admin (has full access)
 *
 * Study case per role:
 * - Employee: 1/3 month attendance + all request types (pending/approved/rejected)
 * - Manager: L1 approval for subordinate requests (leaves, OT, reimb, corrections, WFH, kasbon)
 * - Finance: L2 approval for reimbursements + kasbon, own attendance/requests
 * - HR: Full admin panel access + has subordinates with requests
 * - Admin: Full superadmin access + attendance data
 *
 * Idempotent: cleans old E2E data first, then re-seeds.
 * Guard: only runs in non-production (or SEED_DEMO=true).
 */
class E2eOperationalDataSeeder extends Seeder
{
    private int $officeHourShiftId;

    private CarbonImmutable $today;

    private Company $company;

    private int $branchId;

    /** @var array<string, int> email → employee_id */
    private array $empMap = [];

    /** @var array<int, int> employee_id → user_id */
    private array $empUserMap = [];

    public function run(): void
    {
        if (app()->isProduction() && ! filter_var(env('SEED_DEMO', false), FILTER_VALIDATE_BOOLEAN)) {
            return;
        }

        $this->today = CarbonImmutable::today();
        $this->company = Company::where('code', 'DKMS-2025')->firstOrFail();
        $this->branchId = $this->company->branches()->where('is_main', true)->value('id');
        $this->officeHourShiftId = Shift::where('name', 'Office Hour')->value('id');

        // Step 0: Clean old E2E operational data
        $this->cleanOldData();

        // Step 1: Ensure all E2E users have employee records
        $this->ensureEmployeeRecords();

        // Step 2: Set up org structure (subordinates)
        $this->setupOrgStructure();

        // Step 3: Seed 1/3 month attendance (Aug 18 – Sep 3, 2026)
        $this->seedAttendance();

        // Step 4: Seed leave balances for current year
        $this->seedLeaveBalances();

        // Step 5: Seed approval requests (L1/L2 chains)
        $this->seedLeaveRequests();
        $this->seedOvertimeRequests();
        $this->seedReimbursementRequests();
        $this->seedAttendanceCorrections();
        $this->seedWfhRequests();
        $this->seedCashAdvances();
        $this->seedShiftSwapRequests();
        $this->seedDocumentRequests();

        // Step 6: Seed schedules for next 14 days
        $this->seedSchedules();

        $this->command?->info('E2eOperationalDataSeeder: complete study case seeded.');
    }

    // =========================================================================
    // STEP 0: Clean old data
    // =========================================================================

    private function cleanOldData(): void
    {
        $e2eUserIds = $this->getE2eUserIds();

        // Delete in FK-safe order (children first)
        DB::table('attendance_corrections')->whereIn('employee_id', $this->getE2eEmployeeIds())->delete();
        DB::table('attendances')->whereIn('employee_id', $this->getE2eEmployeeIds())->delete();
        DB::table('schedules')->whereIn('user_id', $e2eUserIds)->delete();
        DB::table('leaves')->whereIn('employee_id', $this->getE2eEmployeeIds())->delete();
        DB::table('leave_balances')->whereIn('employee_id', $this->getE2eEmployeeIds())->delete();
        DB::table('overtimes')->whereIn('employee_id', $this->getE2eEmployeeIds())->delete();
        DB::table('reimbursements')->whereIn('employee_id', $this->getE2eEmployeeIds())->delete();
        DB::table('attendance_corrections')->whereIn('employee_id', $this->getE2eEmployeeIds())->delete();
        DB::table('cash_advances')->whereIn('user_id', $e2eUserIds)->delete();
        DB::table('work_from_home_requests')->whereIn('user_id', $e2eUserIds)->delete();
        DB::table('shift_swap_requests')->whereIn('user_id', $e2eUserIds)->delete();
        DB::table('employee_document_requests')->whereIn('employee_id', $this->getE2eEmployeeIds())->delete();

        $this->command?->info('Cleaned old E2E operational data.');
    }

    // =========================================================================
    // STEP 1: Ensure employee records
    // =========================================================================

    private function ensureEmployeeRecords(): void
    {
        $users = [
            'employee@hrconnect.test' => [
                'name' => 'Budi Santoso', 'emp_no' => 'EMP-E2E-001', 'nik' => '3276010000000001',
                'div' => 'IT', 'pos' => 'IT-STAFF', 'gender' => Gender::LAKI_LAKI,
            ],
            'hr@hrconnect.test' => [
                'name' => 'Siti Rahmawati', 'emp_no' => 'EMP-E2E-002', 'nik' => '3276010000000002',
                'div' => 'HR', 'pos' => 'HR-MGR', 'gender' => Gender::PEREMPUAN,
            ],
            'manager@hrconnect.test' => [
                'name' => 'Dewi Lestari', 'emp_no' => 'EMP-E2E-004', 'nik' => '3276010000000004',
                'div' => 'OPS', 'pos' => 'OPS-MGR', 'gender' => Gender::PEREMPUAN,
            ],
            'finance@hrconnect.test' => [
                'name' => 'Agus Wijaya', 'emp_no' => 'EMP-E2E-005', 'nik' => '3276010000000005',
                'div' => 'FIN', 'pos' => 'FIN-STAFF', 'gender' => Gender::LAKI_LAKI,
            ],
        ];

        $divisions = Division::whereIn('code', ['IT', 'HR', 'FIN', 'OPS'])->get()->keyBy('code');
        $positions = Position::whereIn('code', ['IT-STAFF', 'HR-MGR', 'OPS-MGR', 'FIN-STAFF'])->get()->keyBy('code');

        foreach ($users as $email => $data) {
            $user = User::where('email', $email)->first();
            if (! $user) {
                continue;
            }

            $emp = Employee::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'company_id' => $this->company->id,
                    'branch_id' => $this->branchId,
                    'division_id' => $divisions[$data['div']]->id,
                    'position_id' => $positions[$data['pos']]->id,
                    'employee_number' => $data['emp_no'],
                    'full_name' => $data['name'],
                    'nik' => $data['nik'],
                    'npwp' => '99.999.999.9-999.'.substr($data['emp_no'], -3),
                    'phone' => '081900000001',
                    'gender' => $data['gender'],
                    'marital_status' => MaritalStatus::SINGLE,
                    'blood_type' => BloodType::O_PLUS,
                    'status' => EmployeeStatus::ACTIVE,
                    'birth_date' => '1995-06-15',
                    'join_date' => '2024-01-01',
                    'education_level' => EducationLevel::BACHELOR,
                    'institution_name' => 'Universitas Indonesia',
                    'major' => 'Teknik Informatika',
                    'graduation_year' => 2018,
                    'salary_type' => SalaryType::MONTHLY,
                    'employment_type' => EmploymentType::PERMANENT,
                    'shift_id' => $this->officeHourShiftId,
                    'address_detail' => 'Jl. Pegambiran No.292 B, Jakarta Timur',
                    'pin' => Hash::make('123456'),
                ]
            );

            $this->empMap[$email] = $emp->id;
            $this->empUserMap[$emp->id] = $user->id;
        }
    }

    // =========================================================================
    // STEP 2: Org structure
    // =========================================================================

    private function setupOrgStructure(): void
    {
        $managerEmpId = $this->empMap['manager@hrconnect.test'] ?? null;
        $hrEmpId = $this->empMap['hr@hrconnect.test'] ?? null;

        // Employee + Finance report to Manager
        if ($managerEmpId) {
            Employee::where('id', $this->empMap['employee@hrconnect.test'] ?? 0)->update(['parent_id' => $managerEmpId]);
            Employee::where('id', $this->empMap['finance@hrconnect.test'] ?? 0)->update(['parent_id' => $managerEmpId]);
        }

        // Assign 3 demo HR staff to report to HR
        if ($hrEmpId) {
            $hrStaffIds = DB::table('employees')
                ->where('division_id', Division::where('code', 'HR')->value('id'))
                ->where('position_id', Position::where('code', 'HR-STAFF')->value('id'))
                ->where('id', '!=', $hrEmpId)
                ->limit(3)
                ->pluck('id')
                ->toArray();

            if (count($hrStaffIds) === 0) {
                // Fallback: just grab 3 HR division employees
                $hrStaffIds = DB::table('employees')
                    ->where('division_id', Division::where('code', 'HR')->value('id'))
                    ->where('id', '!=', $hrEmpId)
                    ->limit(3)
                    ->pluck('id')
                    ->toArray();
            }

            foreach ($hrStaffIds as $staffId) {
                DB::table('employees')->where('id', $staffId)->update(['parent_id' => $hrEmpId]);
            }
        }

        $this->command?->info('Org structure set: Manager has 2 subs, HR has '.count($hrStaffIds ?? []).' subs.');
    }

    // =========================================================================
    // STEP 3: 1/3 month attendance (Aug 18 – Sep 3)
    // =========================================================================

    private function seedAttendance(): void
    {
        // 3 bulan penuh: Juni - Agustus 2026 (~66 hari kerja)
        $start = CarbonImmutable::create(2026, 6, 1);
        $end = CarbonImmutable::create(2026, 8, 31);

        // All E2E employees + their subordinates
        $allEmpIds = $this->getE2eEmployeeIds();
        // Also include HR's subordinates
        $hrSubs = DB::table('employees')->where('parent_id', $this->empMap['hr@hrconnect.test'] ?? 0)->pluck('id')->toArray();
        $allEmpIds = array_unique(array_merge($allEmpIds, $hrSubs));

        foreach ($allEmpIds as $empId) {
            $userId = $this->empUserMap[$empId] ?? DB::table('employees')->where('id', $empId)->value('user_id');
            if (! $userId) {
                continue;
            }

            $email = DB::table('users')->where('id', $userId)->value('email') ?? '';
            $shiftId = $this->officeHourShiftId;

            $cursor = $start;
            while ($cursor->lte($end)) {
                // Skip weekends
                if ($cursor->isWeekend()) {
                    $cursor = $cursor->addDay();

                    continue;
                }

                // Deterministic status from hash
                $roll = (crc32($email.'|'.$cursor->toDateString()) & 0x7FFFFFFF) % 100;

                // Distribution: 65% on_time, 15% late, 8% absent, 7% permission, 5% missed_clock_out
                $status = match (true) {
                    $roll < 65 => 'on_time',
                    $roll < 80 => 'late',
                    $roll < 88 => 'absent',
                    $roll < 95 => 'permission',
                    default => 'missed_clock_out',
                };

                $clockIn = null;
                $clockOut = null;
                $lateMinutes = 0;

                match ($status) {
                    'on_time' => [
                        $clockIn = $cursor->setTime(8, $roll % 15),
                        $clockOut = $cursor->setTime(17, $roll % 30),
                    ],
                    'late' => [
                        $lateMinutes = 15 + ($roll % 46), // 15-60 min
                        $clockIn = $cursor->setTime(8, 0)->addMinutes($lateMinutes),
                        $clockOut = $cursor->setTime(17, $roll % 30),
                    ],
                    'permission' => [
                        $clockIn = $cursor->setTime(8, $roll % 30),
                        $clockOut = $cursor->setTime(17, $roll % 30),
                    ],
                    'missed_clock_out' => [
                        $clockIn = $cursor->setTime(8, $roll % 15),
                        $clockOut = null,
                    ],
                    default => null, // absent: no clock
                };

                Attendance::firstOrCreate(
                    ['employee_id' => $empId, 'date' => $cursor->toDateString()],
                    [
                        'shift_id' => $shiftId,
                        'clock_in' => $clockIn,
                        'clock_out' => $clockOut,
                        'status' => $status,
                        'late_minutes' => $lateMinutes,
                        'is_wfa' => false,
                        'verification_method' => in_array($status, ['on_time', 'late', 'permission']) ? 'face_verified' : null,
                    ]
                );

                $cursor = $cursor->addDay();
            }
        }

        $count = DB::table('attendances')->whereIn('employee_id', $allEmpIds)->count();
        $this->command?->info("Attendance seeded: {$count} records for ".count($allEmpIds).' employees.');
    }

    // =========================================================================
    // STEP 4: Leave balances
    // =========================================================================

    private function seedLeaveBalances(): void
    {
        $annualType = LeaveType::where('code', 'ANNUAL')->first();
        $sickType = LeaveType::where('code', 'SICK')->first();
        $year = (int) $this->today->format('Y');

        foreach ($this->empMap as $email => $empId) {
            if ($annualType) {
                LeaveBalance::firstOrCreate(
                    ['employee_id' => $empId, 'leave_type_id' => $annualType->id, 'year' => $year],
                    ['quota' => 12, 'used' => 0, 'carry_forward' => 0]
                );
            }
            if ($sickType) {
                LeaveBalance::firstOrCreate(
                    ['employee_id' => $empId, 'leave_type_id' => $sickType->id, 'year' => $year],
                    ['quota' => 12, 'used' => 0, 'carry_forward' => 0]
                );
            }
        }
    }

    // =========================================================================
    // STEP 5a: Leave requests (L1 manager → L2 HR)
    // =========================================================================

    private function seedLeaveRequests(): void
    {
        $annualType = LeaveType::where('code', 'ANNUAL')->first();
        $sickType = LeaveType::where('code', 'SICK')->first();
        if (! $annualType) {
            return;
        }

        $managerEmpId = $this->empMap['manager@hrconnect.test'];
        $employeeEmpId = $this->empMap['employee@hrconnect.test'];
        $financeEmpId = $this->empMap['finance@hrconnect.test'];

        // --- Employee requests (subordinate of manager) ---

        // 1. Pending leave (waiting L1 manager approval)
        Leave::firstOrCreate(
            ['employee_id' => $employeeEmpId, 'leave_type_id' => $annualType->id, 'start_date' => $this->today->addDays(14)->toDateString()],
            ['end_date' => $this->today->addDays(16)->toDateString(), 'day_type' => 'full_day', 'total_days' => 3,
                'reason' => 'Cuti tahunan — keperluan keluarga', 'status' => RequestStatus::PENDING->value]
        );

        // approver_id FK references employees table, so use employee IDs not user IDs
        $managerEmpId = $this->empMap['manager@hrconnect.test'] ?? 0;
        $hrEmpId = $this->empMap['hr@hrconnect.test'] ?? 0;

        // 2. Approved L1 (waiting L2 HR approval)
        $leaveL1 = Leave::firstOrCreate(
            ['employee_id' => $employeeEmpId, 'leave_type_id' => $sickType?->id ?? $annualType->id, 'start_date' => $this->today->addDays(20)->toDateString()],
            ['end_date' => $this->today->addDays(20)->toDateString(), 'day_type' => 'full_day', 'total_days' => 1,
                'reason' => 'Sakit demam — sudah disetujui atasan', 'status' => RequestStatus::APPROVED_L1->value]
        );
        // L1 approval record
        DB::table('approvals')->updateOrInsert(
            ['approvable_type' => Leave::class, 'approvable_id' => $leaveL1->id, 'approver_id' => $managerEmpId],
            ['level' => 1, 'status' => 'approved', 'approved_at' => $this->today->subDay()]
        );
        // L2 pending record (waiting HR)
        DB::table('approvals')->updateOrInsert(
            ['approvable_type' => Leave::class, 'approvable_id' => $leaveL1->id, 'approver_id' => $hrEmpId],
            ['level' => 2, 'status' => 'pending']
        );

        // 3. Fully approved leave
        $leaveApproved = Leave::firstOrCreate(
            ['employee_id' => $employeeEmpId, 'leave_type_id' => $annualType->id, 'start_date' => $this->today->subDays(10)->toDateString()],
            ['end_date' => $this->today->subDays(9)->toDateString(), 'day_type' => 'full_day', 'total_days' => 2,
                'reason' => 'Cuti sudah disetujui penuh', 'status' => RequestStatus::APPROVED->value]
        );
        DB::table('approvals')->updateOrInsert(
            ['approvable_type' => Leave::class, 'approvable_id' => $leaveApproved->id, 'approver_id' => $managerEmpId],
            ['level' => 1, 'status' => 'approved', 'approved_at' => $this->today->subDays(11)]
        );
        DB::table('approvals')->updateOrInsert(
            ['approvable_type' => Leave::class, 'approvable_id' => $leaveApproved->id, 'approver_id' => $hrEmpId],
            ['level' => 2, 'status' => 'approved', 'approved_at' => $this->today->subDays(10)]
        );

        // 4. Rejected leave
        $leaveRejected = Leave::firstOrCreate(
            ['employee_id' => $employeeEmpId, 'leave_type_id' => $annualType->id, 'start_date' => $this->today->subDays(20)->toDateString()],
            ['end_date' => $this->today->subDays(19)->toDateString(), 'day_type' => 'full_day', 'total_days' => 2,
                'reason' => 'Cuti ditolak — sudah melewati kuota', 'status' => RequestStatus::REJECTED->value,
                'rejection_reason' => 'Kuota cuti tahunan sudah habis']
        );
        DB::table('approvals')->updateOrInsert(
            ['approvable_type' => Leave::class, 'approvable_id' => $leaveRejected->id, 'approver_id' => $managerEmpId],
            ['level' => 1, 'status' => 'rejected', 'notes' => 'Kuota cuti tahunan sudah habis']
        );

        // --- Finance requests (also subordinate of manager) ---
        if ($financeEmpId) {
            $financeLeave = Leave::firstOrCreate(
                ['employee_id' => $financeEmpId, 'leave_type_id' => $annualType->id, 'start_date' => $this->today->addDays(10)->toDateString()],
                ['end_date' => $this->today->addDays(11)->toDateString(), 'day_type' => 'full_day', 'total_days' => 2,
                    'reason' => 'Cuti finance — menunggu persetujuan manager', 'status' => RequestStatus::PENDING->value]
            );
            DB::table('approvals')->updateOrInsert(
                ['approvable_type' => Leave::class, 'approvable_id' => $financeLeave->id, 'approver_id' => $managerEmpId],
                ['level' => 1, 'status' => 'pending']
            );
        }

        // --- Manager own requests (approved at L1, auto-approved as manager) ---
        $mgrLeave = Leave::firstOrCreate(
            ['employee_id' => $managerEmpId, 'leave_type_id' => $annualType->id, 'start_date' => $this->today->subDays(5)->toDateString()],
            ['end_date' => $this->today->subDays(4)->toDateString(), 'day_type' => 'full_day', 'total_days' => 2,
                'reason' => 'Cuti manager sudah disetujui', 'status' => RequestStatus::APPROVED->value]
        );
        DB::table('approvals')->updateOrInsert(
            ['approvable_type' => Leave::class, 'approvable_id' => $mgrLeave->id, 'approver_id' => $hrEmpId],
            ['level' => 2, 'status' => 'approved', 'approved_at' => $this->today->subDays(4)]
        );

        $this->command?->info('Leave requests seeded: 4 (employee) + 1 (finance) + 1 (manager)');
    }

    // =========================================================================
    // STEP 5b: Overtime requests
    // =========================================================================

    private function seedOvertimeRequests(): void
    {
        $employeeEmpId = $this->empMap['employee@hrconnect.test'];
        $financeEmpId = $this->empMap['finance@hrconnect.test'];
        $managerEmpId = $this->empMap['manager@hrconnect.test'];

        $otDate1 = $this->today->subDays(1);
        while ($otDate1->isWeekend()) {
            $otDate1 = $otDate1->subDay();
        }
        $otDate2 = $this->today->subDays(3);
        while ($otDate2->isWeekend()) {
            $otDate2 = $otDate2->subDay();
        }

        // Employee: pending OT
        Overtime::firstOrCreate(
            ['employee_id' => $employeeEmpId, 'date' => $otDate1->toDateString(), 'start_time' => '18:00:00', 'end_time' => '21:00:00'],
            ['description' => 'Selesaikan laporan bulanan', 'total_hours' => 3, 'status' => RequestStatus::PENDING->value]
        );

        // Employee: approved OT
        Overtime::firstOrCreate(
            ['employee_id' => $employeeEmpId, 'date' => $otDate2->toDateString(), 'start_time' => '18:00:00', 'end_time' => '20:00:00'],
            ['description' => 'Lembur sudah disetujui', 'total_hours' => 2, 'status' => RequestStatus::APPROVED->value,
                'approved_at' => $otDate2->addDay()->setTime(9, 0)]
        );

        // Finance: pending OT (for manager to approve)
        Overtime::firstOrCreate(
            ['employee_id' => $financeEmpId, 'date' => $otDate1->toDateString(), 'start_time' => '18:00:00', 'end_time' => '20:00:00'],
            ['description' => 'Lembur finance — pending manager', 'total_hours' => 2, 'status' => RequestStatus::PENDING->value]
        );

        // Manager: own approved OT
        Overtime::firstOrCreate(
            ['employee_id' => $managerEmpId, 'date' => $otDate2->toDateString(), 'start_time' => '18:00:00', 'end_time' => '21:00:00'],
            ['description' => 'Lembur manager', 'total_hours' => 3, 'status' => RequestStatus::APPROVED->value,
                'approved_at' => $otDate2->addDay()->setTime(9, 0)]
        );

        $this->command?->info('Overtime requests seeded: 3 (employee) + 1 (finance) + 1 (manager)');
    }

    // =========================================================================
    // STEP 5c: Reimbursement requests (L1 manager → L2 finance)
    // =========================================================================

    private function seedReimbursementRequests(): void
    {
        $transportCat = ReimbursementCategory::where('name', 'Transportasi')->first();
        $mealCat = ReimbursementCategory::where('name', 'Makan & Jamuan')->first();
        $officeCat = ReimbursementCategory::where('name', 'Perlengkapan Kerja')->first();
        if (! $transportCat) {
            return;
        }

        $employeeEmpId = $this->empMap['employee@hrconnect.test'];
        $financeEmpId = $this->empMap['finance@hrconnect.test'];

        // Employee: pending (waiting L1 manager)
        Reimbursement::firstOrCreate(
            ['employee_id' => $employeeEmpId, 'category_id' => $transportCat->id, 'title' => 'Biaya Transport ke Klien', 'expense_date' => $this->today->subDays(2)->toDateString()],
            ['amount' => 150000, 'description' => 'Grab ke kantor klien', 'status' => ReimbursementStatus::PENDING->value]
        );

        // Employee: approved L1 (waiting L2 finance)
        Reimbursement::firstOrCreate(
            ['employee_id' => $employeeEmpId, 'category_id' => $mealCat?->id ?? $transportCat->id, 'title' => 'Jamuan Klien PT ABC', 'expense_date' => $this->today->subDays(5)->toDateString()],
            ['amount' => 350000, 'description' => 'Makan siang dengan klien', 'status' => ReimbursementStatus::APPROVED_L1->value,
                'head_approved_by' => DB::table('users')->where('email', 'manager@hrconnect.test')->value('id'),
                'head_approved_at' => $this->today->subDays(4)]
        );

        // Employee: fully approved
        Reimbursement::firstOrCreate(
            ['employee_id' => $employeeEmpId, 'category_id' => $officeCat?->id ?? $transportCat->id, 'title' => 'Perlengkapan Kerja', 'expense_date' => $this->today->subDays(10)->toDateString()],
            ['amount' => 250000, 'description' => 'Beli keyboard wireless', 'status' => ReimbursementStatus::APPROVED->value,
                'head_approved_by' => DB::table('users')->where('email', 'manager@hrconnect.test')->value('id'),
                'finance_approved_by' => DB::table('users')->where('email', 'finance@hrconnect.test')->value('id')]
        );

        // Employee: rejected
        Reimbursement::firstOrCreate(
            ['employee_id' => $employeeEmpId, 'category_id' => $transportCat->id, 'title' => 'Transport Non-Kantor', 'expense_date' => $this->today->subDays(15)->toDateString()],
            ['amount' => 100000, 'description' => 'Ditolak — bukan keperluan kantor', 'status' => ReimbursementStatus::REJECTED->value,
                'rejection_reason' => 'Bukan keperluan kantor']
        );

        // Finance: own pending reimbursement
        Reimbursement::firstOrCreate(
            ['employee_id' => $financeEmpId, 'category_id' => $transportCat->id, 'title' => 'Biaya Transport Finance', 'expense_date' => $this->today->subDays(3)->toDateString()],
            ['amount' => 200000, 'description' => 'Transport meeting kantor pusat', 'status' => ReimbursementStatus::PENDING->value]
        );

        // Another pending for finance L2 processing (from employee, already L1 approved)
        Reimbursement::firstOrCreate(
            ['employee_id' => $employeeEmpId, 'category_id' => $mealCat?->id ?? $transportCat->id, 'title' => 'Makan Tim Meeting', 'expense_date' => $this->today->subDays(1)->toDateString()],
            ['amount' => 500000, 'description' => 'Makan bersama tim setelah meeting', 'status' => ReimbursementStatus::PENDING_FINANCE->value,
                'head_approved_by' => DB::table('users')->where('email', 'manager@hrconnect.test')->value('id'),
                'head_approved_at' => $this->today->subDay()]
        );

        $this->command?->info('Reimbursement requests seeded: 5 (employee) + 1 (finance)');
    }

    // =========================================================================
    // STEP 5d: Attendance corrections
    // =========================================================================

    private function seedAttendanceCorrections(): void
    {
        $employeeEmpId = $this->empMap['employee@hrconnect.test'];
        $employeeUserId = $this->empUserMap[$employeeEmpId];

        // Find an attendance with missed_clock_out
        $corrDate = $this->today->subDays(3);
        while ($corrDate->isWeekend()) {
            $corrDate = $corrDate->subDay();
        }

        // Create attendance record if not exists
        $attendance = Attendance::firstOrCreate(
            ['employee_id' => $employeeEmpId, 'date' => $corrDate->toDateString()],
            [
                'shift_id' => $this->officeHourShiftId,
                'clock_in' => $corrDate->setTime(8, 5),
                'clock_out' => null,
                'status' => 'missed_clock_out',
                'late_minutes' => 0,
                'is_wfa' => false,
            ]
        );

        // Pending correction (for manager to approve)
        AttendanceCorrection::firstOrCreate(
            ['employee_id' => $employeeEmpId, 'user_id' => $employeeUserId, 'attendance_date' => $corrDate->toDateString()],
            [
                'attendance_id' => $attendance->id,
                'request_type' => 'missing_check_out',
                'requested_time_out' => $corrDate->setTime(17, 15),
                'reason' => 'Lupa absen pulang — sudah pulang jam 17:15',
                'status' => AttendanceCorrection::STATUS_PENDING,
            ]
        );

        // Approved correction
        $corrDate2 = $this->today->subDays(7);
        while ($corrDate2->isWeekend()) {
            $corrDate2 = $corrDate2->subDay();
        }

        $att2 = Attendance::firstOrCreate(
            ['employee_id' => $employeeEmpId, 'date' => $corrDate2->toDateString()],
            [
                'shift_id' => $this->officeHourShiftId,
                'clock_in' => $corrDate2->setTime(8, 10),
                'clock_out' => null,
                'status' => 'missed_clock_out',
                'late_minutes' => 0,
                'is_wfa' => false,
            ]
        );

        AttendanceCorrection::firstOrCreate(
            ['employee_id' => $employeeEmpId, 'user_id' => $employeeUserId, 'attendance_date' => $corrDate2->toDateString()],
            [
                'attendance_id' => $att2->id,
                'request_type' => 'missing_check_out',
                'requested_time_out' => $corrDate2->setTime(17, 30),
                'reason' => 'Koreksi sudah disetujui',
                'status' => AttendanceCorrection::STATUS_APPROVED,
                'approved_by' => DB::table('users')->where('email', 'manager@hrconnect.test')->value('id'),
                'approved_at' => $corrDate2->addDay(),
            ]
        );

        $this->command?->info('Attendance corrections seeded: 2 (1 pending, 1 approved)');
    }

    // =========================================================================
    // STEP 5e: WFH requests
    // =========================================================================

    private function seedWfhRequests(): void
    {
        $employeeEmpId = $this->empMap['employee@hrconnect.test'];
        $employeeUserId = $this->empUserMap[$employeeEmpId];

        // Pending WFH
        $wfhDate = $this->today->addDays(5);
        WorkFromHomeRequest::firstOrCreate(
            ['user_id' => $employeeUserId, 'start_date' => $wfhDate->toDateString()],
            [
                'company_id' => $this->company->id,
                'end_date' => $wfhDate->toDateString(),
                'start_time' => '08:00',
                'end_time' => '17:00',
                'location_address' => 'Jl. Rumah No. 123, Jakarta Selatan',
                'reason' => 'WFH karena ada perbaikan rumah',
                'status' => WorkFromHomeRequest::STATUS_PENDING,
            ]
        );

        // Approved WFH
        $wfhApproved = $this->today->subDays(8);
        WorkFromHomeRequest::firstOrCreate(
            ['user_id' => $employeeUserId, 'start_date' => $wfhApproved->toDateString()],
            [
                'company_id' => $this->company->id,
                'end_date' => $wfhApproved->toDateString(),
                'start_time' => '08:00',
                'end_time' => '17:00',
                'location_address' => 'Jl. Rumah No. 123, Jakarta Selatan',
                'reason' => 'WFH sudah disetujui',
                'status' => WorkFromHomeRequest::STATUS_APPROVED,
            ]
        );

        // Rejected WFH
        $wfhRejected = $this->today->subDays(15);
        WorkFromHomeRequest::firstOrCreate(
            ['user_id' => $employeeUserId, 'start_date' => $wfhRejected->toDateString()],
            [
                'company_id' => $this->company->id,
                'end_date' => $wfhRejected->toDateString(),
                'start_time' => '08:00',
                'end_time' => '17:00',
                'location_address' => 'Jl. Pantai No. 5, Bali',
                'reason' => 'WFH dari Bali — ditolak',
                'status' => WorkFromHomeRequest::STATUS_REJECTED,
                'rejection_reason' => 'Lokasi terlalu jauh dari kantor',
            ]
        );

        $this->command?->info('WFH requests seeded: 3 (1 pending, 1 approved, 1 rejected)');
    }

    // =========================================================================
    // STEP 5f: Cash advances
    // =========================================================================

    private function seedCashAdvances(): void
    {
        $employeeEmpId = $this->empMap['employee@hrconnect.test'];
        $employeeUserId = $this->empUserMap[$employeeEmpId];

        // Pending cash advance
        CashAdvance::firstOrCreate(
            ['user_id' => $employeeUserId, 'purpose' => 'Kasbon transport proyek klien'],
            [
                'amount' => 500000,
                'status' => 'pending',
                'payment_month' => (int) $this->today->format('m'),
                'payment_year' => (int) $this->today->format('Y'),
            ]
        );

        // Approved cash advance
        $prevMonth = $this->today->subMonth();
        CashAdvance::firstOrCreate(
            ['user_id' => $employeeUserId, 'purpose' => 'Kasbon sudah disetujui'],
            [
                'amount' => 300000,
                'status' => 'approved',
                'payment_month' => (int) $prevMonth->format('m'),
                'payment_year' => (int) $prevMonth->format('Y'),
                'approved_at' => $prevMonth->subDay(),
            ]
        );

        $this->command?->info('Cash advances seeded: 2 (1 pending, 1 approved)');
    }

    // =========================================================================
    // STEP 5g: Shift swap requests
    // =========================================================================

    private function seedShiftSwapRequests(): void
    {
        $employeeEmpId = $this->empMap['employee@hrconnect.test'];
        $employeeUserId = $this->empUserMap[$employeeEmpId];
        $flexibleShiftId = Shift::where('name', 'Flexible')->value('id');
        if (! $flexibleShiftId) {
            return;
        }

        $swapDate = $this->today->addDays(7);
        while ($swapDate->isWeekend()) {
            $swapDate = $swapDate->addDay();
        }

        $schedule = Schedule::firstOrCreate(
            ['user_id' => $employeeUserId, 'date' => $swapDate->toDateString()],
            ['shift_id' => $this->officeHourShiftId, 'is_off' => false]
        );

        // Find another active employee to swap with
        $targetEmp = DB::table('employees')
            ->where('id', '!=', $employeeEmpId)
            ->where('status', 'active')
            ->where('shift_id', $flexibleShiftId)
            ->first();

        if ($targetEmp) {
            ShiftSwapRequest::firstOrCreate(
                ['user_id' => $employeeUserId, 'schedule_id' => $schedule->id, 'schedule_date' => $swapDate->toDateString()],
                [
                    'requester_id' => $employeeEmpId,
                    'target_id' => $targetEmp->id,
                    'current_shift_id' => $this->officeHourShiftId,
                    'requested_shift_id' => $flexibleShiftId,
                    'reason' => 'Tukar shift karena ada keperluan',
                    'status' => ShiftSwapRequest::STATUS_PENDING,
                ]
            );
        }

        $this->command?->info('Shift swap seeded: 1 pending');
    }

    // =========================================================================
    // STEP 5h: Document requests
    // =========================================================================

    private function seedDocumentRequests(): void
    {
        $employeeEmpId = $this->empMap['employee@hrconnect.test'];

        // Find a document template
        $template = EmployeeDocumentTemplate::first();
        if (! $template) {
            return;
        }

        EmployeeDocumentRequest::firstOrCreate(
            ['employee_id' => $employeeEmpId, 'document_type_id' => $template->document_type_id ?? 1, 'purpose' => 'Surat keterangan kerja untuk bank'],
            [
                'requested_by' => $this->empUserMap[$employeeEmpId],
                'request_source' => 'employee',
                'details' => 'Diperlukan untuk pengajuan KPR',
                'status' => 'pending',
            ]
        );

        $this->command?->info('Document request seeded: 1 pending');
    }

    // =========================================================================
    // STEP 6: Schedules
    // =========================================================================

    private function seedSchedules(): void
    {
        $allUserIds = array_values($this->empUserMap);
        // Also include HR subordinates
        $hrSubUserIds = DB::table('employees')
            ->where('parent_id', $this->empMap['hr@hrconnect.test'] ?? 0)
            ->pluck('user_id')
            ->toArray();
        $allUserIds = array_unique(array_merge($allUserIds, $hrSubUserIds));

        $start = $this->today;
        $end = $this->today->addDays(14);

        foreach ($allUserIds as $userId) {
            $cursor = $start->copy();
            while ($cursor->lte($end)) {
                Schedule::firstOrCreate(
                    ['user_id' => $userId, 'date' => $cursor->toDateString()],
                    [
                        'shift_id' => $cursor->isWeekend() ? null : $this->officeHourShiftId,
                        'is_off' => $cursor->isWeekend(),
                    ]
                );
                $cursor = $cursor->addDay();
            }
        }
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    private function getE2eUserIds(): array
    {
        return DB::table('users')
            ->whereIn('email', [
                'employee@hrconnect.test',
                'hr@hrconnect.test',
                'manager@hrconnect.test',
                'finance@hrconnect.test',
                'admin@hrconnect.local',
            ])
            ->pluck('id')
            ->toArray();
    }

    private function getE2eEmployeeIds(): array
    {
        return DB::table('employees')
            ->whereIn('user_id', $this->getE2eUserIds())
            ->pluck('id')
            ->toArray();
    }
}

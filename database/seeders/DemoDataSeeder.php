<?php

namespace Database\Seeders;

use App\Enums\EmployeeStatus;
use App\Enums\SalaryType;
use App\Models\Attendance;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\Overtime;
use App\Models\Position;
use App\Models\Reimbursement;
use App\Models\ReimbursementCategory;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::where('code', 'HRCONNECT')->first();
        if (! $company) {
            $this->command?->warn('Company HRCONNECT belum ada. Jalankan CompanyAndDepartmentSeeder dulu.');

            return;
        }

        if (Employee::count() > 5) {
            $this->command?->info('Demo data sudah ada ('.Employee::count().' karyawan). Skip.');

            return;
        }

        $itStaffPos = Position::where('code', 'IT-STAFF')->first();
        $hrStaffPos = Position::where('code', 'HR-STAFF')->first();
        $finStaffPos = Position::where('code', 'FIN-STAFF')->first();
        $itMgrPos = Position::where('code', 'IT-MGR')->first();
        $hrMgrPos = Position::where('code', 'HR-MGR')->first();
        $finMgrPos = Position::where('code', 'FIN-MGR')->first();

        $branch = $company->branches()->first();
        $dept = $company->branches()->first()?->departments()->first();
        $shiftId = Shift::first()?->id;

        $demoUsers = [
            ['name' => 'Admin HR', 'email' => 'admin@hrconnect.local', 'role' => 'hr-manager'],
            ['name' => 'Andi Manager', 'email' => 'manager@hrconnect.local', 'role' => 'manager'],
            ['name' => 'Budi Staff', 'email' => 'staff@hrconnect.local', 'role' => 'employee'],
            ['name' => 'Citra Finance', 'email' => 'finance@hrconnect.local', 'role' => 'finance'],
        ];

        $now = now();
        foreach ($demoUsers as $i => $u) {
            $user = User::firstOrCreate(
                ['email' => $u['email']],
                [
                    'name' => $u['name'],
                    'password' => Hash::make('password'),
                    'email_verified_at' => $now,
                ]
            );
            $user->assignRole($u['role']);

            $pos = match ($u['role']) {
                'hr-manager' => $hrMgrPos,
                'manager' => $itMgrPos,
                'finance' => $finMgrPos,
                default => $itStaffPos,
            };

            $employee = Employee::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'company_id' => $company->id,
                    'branch_id' => $branch?->id,
                    'department_id' => $dept?->id,
                    'position_id' => $pos?->id,
                    'shift_id' => $shiftId,
                    'employee_number' => 'DEMO-'.str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT),
                    'full_name' => $u['name'],
                    'gender' => 'L',
                    'status' => EmployeeStatus::ACTIVE,
                    'join_date' => $now->subMonths(12)->toDateString(),
                    'salary_type' => SalaryType::MONTHLY,
                ]
            );

            $leaveTypes = LeaveType::all();
            foreach ($leaveTypes as $lt) {
                if ($lt->deducts_from_quota && $lt->quota > 0) {
                    LeaveBalance::firstOrCreate(
                        ['employee_id' => $employee->id, 'leave_type_id' => $lt->id, 'year' => $now->year],
                        ['total_quota' => $lt->quota, 'used_quota' => 0]
                    );
                }
            }
        }

        $leaveType = LeaveType::first();
        $reimbCategory = ReimbursementCategory::firstOrCreate(
            ['code' => 'RB_TRANSPORT'],
            ['company_id' => $company->id, 'name' => 'Transportasi', 'is_active' => true]
        );

        $demoEmployees = Employee::all();
        foreach ($demoEmployees as $emp) {
            if (rand(0, 1)) {
                Attendance::factory()->create([
                    'employee_id' => $emp->id,
                    'date' => $now->copy()->subDays(rand(1, 5))->toDateString(),
                ]);
            }

            if (rand(0, 1) && $leaveType) {
                Leave::factory()->create([
                    'employee_id' => $emp->id,
                    'leave_type_id' => $leaveType->id,
                    'start_date' => $now->copy()->addDays(rand(5, 20))->toDateString(),
                    'end_date' => $now->copy()->addDays(rand(5, 20))->toDateString(),
                    'total_days' => 1,
                ]);
            }

            if (rand(0, 1)) {
                Overtime::factory()->create([
                    'employee_id' => $emp->id,
                    'date' => $now->copy()->subDays(rand(1, 10))->toDateString(),
                    'total_hours' => 2,
                    'amount' => 50000,
                ]);
            }

            if (rand(0, 1)) {
                Reimbursement::factory()->create([
                    'employee_id' => $emp->id,
                    'category_id' => $reimbCategory->id,
                    'amount' => rand(100000, 500000),
                ]);
            }
        }

        $this->command?->info('DemoDataSeeder: '.Employee::count().' karyawan demo siap.');
    }
}

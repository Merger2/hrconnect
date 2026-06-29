<?php

namespace Database\Seeders;

use App\Enums\AssetStatus;
use App\Enums\EmployeeStatus;
use App\Enums\LoanStatus;
use App\Enums\SalaryType;
use App\Models\Asset;
use App\Models\Attendance;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\Loan;
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

        $this->command?->info('DemoDataSeeder: memastikan demo users & employees...');

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
                    'password_changed_at' => $now,
                ]
            );
            $user->assignRole($u['role']);

            $pos = match ($u['role']) {
                'hr-manager' => $hrMgrPos,
                'manager' => $itMgrPos,
                'finance' => $finMgrPos,
                default => $itStaffPos,
            };

            $suffix = $i + 1;
            $employee = Employee::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'company_id' => $company->id,
                    'branch_id' => $branch?->id,
                    'department_id' => $dept?->id,
                    'position_id' => $pos?->id,
                    'shift_id' => $shiftId,
                    'employee_number' => 'DEMO-'.str_pad((string) $suffix, 3, '0', STR_PAD_LEFT),
                    'full_name' => $u['name'],
                    'gender' => 'L',
                    'status' => EmployeeStatus::ACTIVE,
                    'join_date' => $now->subMonths(12)->toDateString(),
                    'salary_type' => SalaryType::MONTHLY,
                    'birth_date' => $now->subYears(rand(22, 40))->toDateString(),
                    'marital_status' => 'single',
                    'blood_type' => fake()->randomElement(['A+', 'B+', 'AB+', 'O+', 'A-', 'B-', 'AB-', 'O-']),
                    'phone' => '0812'.str_pad((string) (1000 + $suffix), 8, '0', STR_PAD_LEFT),
                    'nik' => '3276'.str_pad((string) $suffix, 12, '0', STR_PAD_LEFT),
                    'npwp' => $suffix.'.'.$suffix.'.'.$suffix.'.'.($suffix * 111).'.000',
                    'bank_account_number' => '12345'.str_pad((string) $suffix, 10, '0', STR_PAD_LEFT),
                    'bank_name' => 'Bank Central Asia (BCA)',
                    'education_level' => fake()->randomElement(['sd', 'smp', 'sma', 'smk', 'diploma', 'bachelor', 'master']),
                    'institution_name' => 'Universitas Indonesia',
                    'major' => 'Manajemen',
                    'graduation_year' => $now->subYears(rand(5, 15))->year,
                ]
            );

            $leaveTypes = LeaveType::all();
            foreach ($leaveTypes as $lt) {
                if ($lt->deducts_from_quota && $lt->quota > 0) {
                    LeaveBalance::firstOrCreate(
                        ['employee_id' => $employee->id, 'leave_type_id' => $lt->id, 'year' => $now->year],
                        ['quota' => $lt->quota, 'used' => 0]
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
            $pastDate = $now->copy()->subDays(rand(1, 5))->toDateString();
            if (rand(0, 1) && ! Attendance::where(['employee_id' => $emp->id, 'date' => $pastDate])->exists()) {
                Attendance::factory()->create([
                    'employee_id' => $emp->id,
                    'date' => $pastDate,
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

        $assetData = [
            ['name' => 'Laptop Dell Latitude 5430', 'code' => 'AST-001', 'status' => AssetStatus::ASSIGNED, 'is_available' => false],
            ['name' => 'Monitor Samsung 24 inch', 'code' => 'AST-002', 'status' => AssetStatus::ASSIGNED, 'is_available' => false],
            ['name' => 'Meja Kerja Ergonomic', 'code' => 'AST-003', 'status' => AssetStatus::AVAILABLE, 'is_available' => true],
            ['name' => 'Kursi Kantor Herman Miller', 'code' => 'AST-004', 'status' => AssetStatus::AVAILABLE, 'is_available' => true],
            ['name' => 'Printer Epson L3210', 'code' => 'AST-005', 'status' => AssetStatus::AVAILABLE, 'is_available' => true],
            ['name' => 'Mouse Logitech MX Master 3', 'code' => 'AST-006', 'status' => AssetStatus::ASSIGNED, 'is_available' => false],
            ['name' => 'Keyboard Mechanical Keychron', 'code' => 'AST-007', 'status' => AssetStatus::ASSIGNED, 'is_available' => false],
            ['name' => 'Proyektor Epson EB-X51', 'code' => 'AST-008', 'status' => AssetStatus::DISPOSED, 'is_available' => false],
            ['name' => 'AC Panasonic 1.5 PK', 'code' => 'AST-009', 'status' => AssetStatus::DISPOSED, 'is_available' => false],
            ['name' => 'iPhone 15 Pro (Company)', 'code' => 'AST-010', 'status' => AssetStatus::ASSIGNED, 'is_available' => false],
        ];

        foreach ($assetData as $i => $a) {
            Asset::withoutEvents(function () use ($company, $a, $i) {
                Asset::firstOrCreate(
                    ['code' => $a['code']],
                    [
                        'company_id' => $company->id,
                        'name' => $a['name'],
                        'serial_number' => 'SN-'.str_pad((string) ($i + 1), 6, '0', STR_PAD_LEFT),
                        'category' => match (true) {
                            str_contains($a['name'], 'Laptop') || str_contains($a['name'], 'Mouse') || str_contains($a['name'], 'Keyboard') || str_contains($a['name'], 'Monitor') || str_contains($a['name'], 'Printer') || str_contains($a['name'], 'Proyektor') || str_contains($a['name'], 'iPhone') => 'elektronik',
                            str_contains($a['name'], 'Meja') || str_contains($a['name'], 'Kursi') => 'furniture',
                            default => 'peralatan',
                        },
                        'status' => $a['status'],
                        'is_available' => $a['is_available'],
                    ]
                );
            });
        }

        $hrUser = User::where('email', 'admin@hrconnect.local')->first();
        foreach ($demoEmployees->take(3) as $emp) {
            if (rand(0, 1)) {
                Loan::factory()->create([
                    'employee_id' => $emp->id,
                    'created_by' => $hrUser?->id ?? 1,
                    'amount' => rand(500000, 3000000),
                    'tenor_months' => rand(3, 12),
                    'monthly_installment' => round(rand(500000, 3000000) / rand(3, 12), 2),
                    'status' => LoanStatus::ACTIVE,
                    'is_settled' => false,
                ]);
            }
        }

        if ($demoEmployees->first() && rand(0, 1)) {
            Loan::factory()->paidOff()->create([
                'employee_id' => $demoEmployees->first()->id,
                'created_by' => $hrUser?->id ?? 1,
            ]);
        }

        $this->command?->info('DemoDataSeeder: '.Employee::count().' karyawan demo, '.Asset::count().' aset, '.Loan::count().' pinjaman siap.');
    }
}

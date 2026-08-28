<?php

namespace Database\Seeders;

use App\Enums\ReimbursementStatus;
use App\Enums\RequestStatus;
use App\Models\Announcement;
use App\Models\CashAdvance;
use App\Models\CompanyAsset;
use App\Models\Employee;
use App\Models\EmployeeDocumentRequest;
use App\Models\EmployeeDocumentTemplate;
use App\Models\Leave;
use App\Models\LeaveType;
use App\Models\Overtime;
use App\Models\Reimbursement;
use App\Models\ReimbursementCategory;
use App\Models\Shift;
use App\Models\ShiftSwapRequest;
use App\Models\User;
use App\Models\WorkFromHomeRequest;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Seeder untuk use case demo lengkap.
 *
 * Mengisi data:
 * - Leave requests (pending, approved, rejected)
 * - Overtime requests (pending, approved)
 * - Reimbursement (pending, approved)
 * - Shift swap requests (pending)
 * - WFH requests (pending, approved)
 * - Document requests (pending)
 * - Cash advances (pending, approved)
 * - Announcements (active)
 * - Company assets (assigned)
 *
 * IDEMPOTENT: aman jalan berkali-kali (firstOrCreate).
 * PRODUCTION GUARD: tidak jalan di production kecuali SEED_DEMO=true.
 */
class DemoUseCaseSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction() && ! filter_var(env('SEED_DEMO', false), FILTER_VALIDATE_BOOLEAN)) {
            return;
        }

        // Get demo employees (employee1-5@hrconnect.local)
        $demoEmployees = Employee::query()
            ->whereHas('user', fn ($q) => $q->where('email', 'like', 'employee%@hrconnect.local'))
            ->where('status', 'active')
            ->get();

        if ($demoEmployees->isEmpty()) {
            $this->command?->warn('No demo employees found. Run CompanyEmployeesSeeder first.');

            return;
        }

        // Get demo managers
        $demoManagers = Employee::query()
            ->whereHas('user', fn ($q) => $q->where('email', 'like', 'employee%@hrconnect.local'))
            ->where('status', 'active')
            ->whereHas('user.roles', fn ($q) => $q->where('name', 'manager'))
            ->get();

        // Get demo admin/HR
        $demoHR = User::where('email', 'hr@hrconnect.test')->first();
        $demoFinance = User::where('email', 'finance@hrconnect.test')->first();

        $this->seedLeaves($demoEmployees, $demoManagers);
        $this->seedOvertime($demoEmployees);
        $this->seedReimbursements($demoEmployees);
        $this->seedShiftSwaps($demoEmployees);
        $this->seedWfhRequests($demoEmployees);
        $this->seedDocumentRequests($demoEmployees);
        $this->seedCashAdvances($demoEmployees);
        $this->seedAnnouncements($demoHR);
        $this->seedAssets($demoEmployees);

        $this->command?->info('✅ DemoUseCaseSeeder completed.');
    }

    private function seedLeaves($employees, $managers): void
    {
        $leaveType = LeaveType::where('name', 'like', '%Cuti Tahunan%')->first()
            ?? LeaveType::first();

        if (! $leaveType) {
            return;
        }

        $statuses = [
            RequestStatus::PENDING,
            RequestStatus::APPROVED,
            RequestStatus::REJECTED,
        ];

        $reasons = [
            'Liburan keluarga',
            'Keperluan pribadi',
            'Istirahat kesehatan',
            'Acara keluarga',
            'Perjalanan wisata',
        ];

        $i = 0;
        foreach ($employees->take(6) as $employee) {
            $status = $statuses[$i % 3];
            $startDate = Carbon::now()->addDays(5 + $i)->toDateString();
            $endDate = Carbon::now()->addDays(6 + $i)->toDateString();

            Leave::firstOrCreate(
                [
                    'employee_id' => $employee->id,
                    'start_date' => $startDate,
                ],
                [
                    'leave_type_id' => $leaveType->id,
                    'end_date' => $endDate,
                    'day_type' => 'full_day',
                    'total_days' => 2,
                    'reason' => $reasons[$i % count($reasons)],
                    'status' => $status,
                    'rejection_reason' => $status === RequestStatus::REJECTED ? 'Kuota cuti tidak mencukupi' : null,
                ]
            );
            $i++;
        }

        $this->command?->info('  ✅ Leave requests seeded');
    }

    private function seedOvertime($employees): void
    {
        $statuses = [
            RequestStatus::PENDING,
            RequestStatus::APPROVED,
        ];

        $descriptions = [
            'Deadline proyek',
            'Maintenance server',
            'Deploy aplikasi',
            'Bug fix kritis',
            'Persiapan presentasi',
        ];

        $i = 0;
        foreach ($employees->take(5) as $employee) {
            $status = $statuses[$i % 2];
            $date = Carbon::now()->subDays(3 + $i)->toDateString();

            Overtime::firstOrCreate(
                [
                    'employee_id' => $employee->id,
                    'date' => $date,
                ],
                [
                    'start_time' => '17:00',
                    'end_time' => '20:00',
                    'description' => $descriptions[$i % count($descriptions)],
                    'total_hours' => '3.00',
                    'amount' => '150000.00',
                    'status' => $status,
                ]
            );
            $i++;
        }

        $this->command?->info('  ✅ Overtime requests seeded');
    }

    private function seedReimbursements($employees): void
    {
        $category = ReimbursementCategory::first();
        if (! $category) {
            return;
        }

        $statuses = [
            ReimbursementStatus::PENDING,
            ReimbursementStatus::APPROVED,
        ];

        $titles = [
            'Transport ke client',
            'Makan siang meeting',
            'Tiket parkir',
            'Beli ATK',
            'Internet bulanan',
        ];

        $i = 0;
        foreach ($employees->take(5) as $employee) {
            $status = $statuses[$i % 2];

            Reimbursement::firstOrCreate(
                [
                    'employee_id' => $employee->id,
                    'title' => $titles[$i],
                ],
                [
                    'category_id' => $category->id,
                    'expense_date' => Carbon::now()->subDays(2 + $i)->toDateString(),
                    'amount' => (50000 + ($i * 25000)),
                    'description' => 'Reimbursement untuk '.$titles[$i],
                    'status' => $status,
                ]
            );
            $i++;
        }

        $this->command?->info('  ✅ Reimbursement requests seeded');
    }

    private function seedShiftSwaps($employees): void
    {
        $shifts = Shift::all();
        if ($shifts->count() < 2) {
            return;
        }

        $i = 0;
        foreach ($employees->take(3) as $employee) {
            // Find a target employee (different from requester)
            $target = $employees->where('id', '!=', $employee->id)->first();
            if (! $target) {
                continue;
            }

            $shift1 = $shifts->first();
            $shift2 = $shifts->last();

            ShiftSwapRequest::firstOrCreate(
                [
                    'requester_id' => $employee->id,
                    'schedule_date' => Carbon::now()->addDays(7 + $i)->toDateString(),
                ],
                [
                    'target_id' => $target->id,
                    'requested_shift_id' => $shift1->id,
                    'current_shift_id' => $shift2->id,
                    'status' => ShiftSwapRequest::STATUS_PENDING,
                ]
            );
            $i++;
        }

        $this->command?->info('  ✅ Shift swap requests seeded');
    }

    private function seedWfhRequests($employees): void
    {
        $statuses = [
            WorkFromHomeRequest::STATUS_PENDING,
            WorkFromHomeRequest::STATUS_APPROVED,
        ];

        $reasons = [
            'Kerja dari rumah karena hujan',
            'WFH karena sakit',
            'Remote work hari Jumat',
            'Kerja dari co-working space',
            'WFH karena internet di kantor ganggu',
        ];

        $i = 0;
        foreach ($employees->take(5) as $employee) {
            $status = $statuses[$i % 2];
            $wfhDate = Carbon::now()->addDays(1 + $i)->toDateString();

            WorkFromHomeRequest::firstOrCreate(
                [
                    'user_id' => $employee->user_id,
                    'start_date' => $wfhDate,
                ],
                [
                    'company_id' => $employee->company_id,
                    'end_date' => $wfhDate,
                    'reason' => $reasons[$i % count($reasons)],
                    'status' => $status,
                ]
            );
            $i++;
        }

        $this->command?->info('  ✅ WFH requests seeded');
    }

    private function seedDocumentRequests($employees): void
    {
        $templates = EmployeeDocumentTemplate::all();
        if ($templates->isEmpty()) {
            $this->command?->warn('  ⚠️ No document templates found — skipping document requests');

            return;
        }

        $i = 0;
        foreach ($employees->take(5) as $employee) {
            $template = $templates->skip($i % $templates->count())->first();

            EmployeeDocumentRequest::firstOrCreate(
                [
                    'employee_id' => $employee->id,
                    'document_type_id' => $template->id,
                ],
                [
                    'purpose' => 'Diperlukan untuk keperluan pribadi',
                    'status' => EmployeeDocumentRequest::STATUS_PENDING,
                ]
            );
            $i++;
        }

        $this->command?->info('  ✅ Document requests seeded');
    }

    private function seedCashAdvances($employees): void
    {
        $purposes = [
            'Biaya transport dinas',
            'Modal proyek',
            'Biaya meeting client',
            'Dana operasional',
            'Biaya cetak dokumen',
        ];

        $i = 0;
        foreach ($employees->take(5) as $employee) {
            CashAdvance::firstOrCreate(
                [
                    'user_id' => $employee->user_id,
                    'purpose' => $purposes[$i],
                ],
                [
                    'amount' => (100000 + ($i * 50000)),
                    'status' => $i % 2 === 0 ? 'pending' : 'approved',
                    'payment_month' => Carbon::now()->month,
                    'payment_year' => Carbon::now()->year,
                ]
            );
            $i++;
        }

        $this->command?->info('  ✅ Cash advances seeded');
    }

    private function seedAnnouncements($createdBy): void
    {
        if (! $createdBy) {
            return;
        }

        $announcements = [
            [
                'title' => 'Libur Nasional Hari Pancasila',
                'content' => 'Diberitahukan bahwa tanggal 1 Juni adalah hari libur nasional. Seluruh karyawan tidak perlu masuk kerja.',
                'priority' => 'high',
            ],
            [
                'title' => 'Pengumuman Cuti Bersama',
                'content' => 'Cuti bersama telah ditetapkan untuk tanggal 28-30 Juni. Mohon untuk mengajukan cuti tepat waktu.',
                'priority' => 'medium',
            ],
            [
                'title' => 'Training K3 Bulan Ini',
                'content' => 'Seluruh karyawan wajib mengikuti pelatihan K3 yang akan dilaksanakan minggu depan.',
                'priority' => 'low',
            ],
        ];

        foreach ($announcements as $ann) {
            Announcement::firstOrCreate(
                ['title' => $ann['title']],
                [
                    'content' => $ann['content'],
                    'priority' => $ann['priority'],
                    'created_by' => $createdBy->id,
                    'is_active' => true,
                    'published_at' => Carbon::now()->subDays(5),
                    'expired_at' => Carbon::now()->addDays(30),
                ]
            );
        }

        $this->command?->info('  ✅ Announcements seeded');
    }

    private function seedAssets($employees): void
    {
        $assets = [
            ['name' => 'Laptop ASUS VivoBook 14', 'serial' => 'SN-LAPTOP-001'],
            ['name' => 'Monitor LG 24 inch', 'serial' => 'SN-MONITOR-001'],
            ['name' => 'Keyboard Mechanical RGB', 'serial' => 'SN-KB-001'],
            ['name' => 'Mouse Logitech MX Master', 'serial' => 'SN-MOUSE-001'],
            ['name' => 'Headset Jabra Evolve2', 'serial' => 'SN-HEADSET-001'],
        ];

        $i = 0;
        foreach ($assets as $assetData) {
            $asset = CompanyAsset::firstOrCreate(
                ['serial_number' => $assetData['serial']],
                [
                    'name' => $assetData['name'],
                    'type' => 'hardware',
                    'status' => CompanyAsset::STATUS_ASSIGNED,
                    'purchase_date' => Carbon::now()->subMonths(6),
                    'purchase_cost' => 5000000 + ($i * 1000000),
                ]
            );

            // Assign to employee
            $employee = $employees->skip($i)->first() ?? $employees->first();
            if ($employee) {
                $asset->update([
                    'user_id' => $employee->user_id,
                    'date_assigned' => Carbon::now()->subMonths(3),
                ]);
            }
            $i++;
        }

        $this->command?->info('  ✅ Company assets seeded');
    }
}

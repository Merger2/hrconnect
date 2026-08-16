<?php

namespace Database\Seeders;

use App\Enums\BloodType;
use App\Enums\EducationLevel;
use App\Enums\EmployeeStatus;
use App\Enums\EmploymentType;
use App\Enums\FamilyRelationship;
use App\Enums\Gender;
use App\Enums\MaritalStatus;
use App\Enums\SalaryType;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Division;
use App\Models\Employee;
use App\Models\FamilyDetail;
use App\Models\Position;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CompanyEmployeesSeeder extends Seeder
{
    private const UNIVERSITIES = [
        'Universitas Indonesia',
        'Institut Teknologi Bandung',
        'Universitas Gadjah Mada',
        'Universitas Brawijaya',
        'BINUS University',
        'Universitas Gunadarma',
        'Universitas Padjadjaran',
        'Universitas Diponegoro',
        'Institut Teknologi Sepuluh Nopember',
        'Universitas Telkom',
        'Universitas Negeri Jakarta',
        'Universitas Kristen Krida Wacana',
    ];

    private const MAJORS_BY_DIV = [
        'IT' => ['Teknik Informatika', 'Sistem Informasi', 'Ilmu Komputer', 'Teknik Elektro'],
        'HR' => ['Psikologi', 'Hukum', 'Manajemen SDM', 'Ilmu Komunikasi'],
        'FIN' => ['Akuntansi', 'Manajemen Keuangan', 'Ekonomi', 'Perbankan'],
        'OPS' => ['Teknik Industri', 'Manajemen Operasional', 'Teknik Sipil', 'Logistik'],
    ];

    private const BLOOD_TYPES = [
        BloodType::A_PLUS, BloodType::A_MINUS, BloodType::B_PLUS,
        BloodType::B_MINUS, BloodType::AB_PLUS, BloodType::O_PLUS, BloodType::O_MINUS,
    ];

    /** @var array<string, Division> */
    private array $divisions = [];

    /** @var array<string, Position> */
    private array $positions = [];

    private Company $company;

    private Branch $branch;

    private Shift $defaultShift;

    private ?Employee $owner = null;

    /** @var array<string, Employee> Manager employees per division code */
    private array $managers = [];

    public function run(): void
    {
        // Demo/test-only: 50 karyawan palsu (@hrconnect.local) dengan password
        // publik ('password'/'owner12345') + NIK/NPWP fiktif — jangan pernah
        // di-seed di production. (Guard kedua: DatabaseSeeder juga skip.)
        // Demo VPS (skripsi): SEED_DEMO=true mengizinkan di production — default off.
        if (app()->isProduction() && ! filter_var(env('SEED_DEMO', false), FILTER_VALIDATE_BOOLEAN)) {
            return;
        }

        $this->company = Company::where('code', 'DKMS-2025')->firstOrFail();
        $this->branch = Branch::where('company_id', $this->company->id)->where('is_main', true)->firstOrFail();
        $this->defaultShift = Shift::where('name', 'Office Hour')->firstOrFail();

        // Load master data
        $this->divisions = Division::whereIn('code', ['IT', 'HR', 'FIN', 'OPS'])->get()->keyBy('code')->all();
        $this->positions = Position::whereIn('code', [
            'IT-STAFF', 'IT-MGR', 'HR-STAFF', 'HR-MGR',
            'FIN-STAFF', 'FIN-MGR', 'OPS-STAFF', 'OPS-MGR',
        ])->get()->keyBy('code')->all();

        // 1. Buat OWNER (super-admin)
        $this->createOwner();

        // 2. Buat 4 MANAGERS (employee1-4)
        $this->createManager('employee1@hrconnect.local', 'IT', 'IT-MGR', 'EMP-0002', 'Budi Santoso');
        $this->createManager('employee2@hrconnect.local', 'HR', 'HR-MGR', 'EMP-0003', 'Siti Rahmawati');
        $this->createManager('employee3@hrconnect.local', 'FIN', 'FIN-MGR', 'EMP-0004', 'Agus Wijaya');
        $this->createManager('employee4@hrconnect.local', 'OPS', 'OPS-MGR', 'EMP-0005', 'Dewi Lestari');

        // 3. Buat 46 STAFF (employee5-50) dengan distribusi ke 4 divisi
        $staffDivisions = ['IT', 'IT', 'IT', 'HR', 'HR', 'FIN', 'FIN', 'OPS', 'OPS', 'OPS'];
        $staffNames = [
            'Ahmad Fauzi', 'Rina Marlina', 'Doni Prasetyo', 'Mega Putri', 'Fajar Hidayat',
            'Nurul Aini', 'Rizky Pratama', 'Wulan Sari', 'Hendra Gunawan', 'Fitri Handayani',
            'Adi Saputra', 'Tiara Maharani', 'Bayu Nugroho', 'Dian Permata', 'Gilang Ramadhan',
            'Indah Permata Sari', 'Joko Susilo', 'Kartika Dewi', 'Leo Pratama', 'Maya Anggraini',
            'Novi Andriani', 'Oscar Situmorang', 'Putri Ayu', 'Rangga Wirawan', 'Sari Dewi',
            'Teguh Prakoso', 'Umi Kalsum', 'Vicky Hermawan', 'Winda Astuti', 'Yoga Pradana',
            'Zahra Amalia', 'Aditya Saputro', 'Bella Safira', 'Candra Kusuma', 'Dinda Kirana',
            'Eko Prasetyo', 'Farah Nabila', 'Galih Prayoga', 'Hana Salsabila', 'Irfan Maulana',
            'Jasmine Putri', 'Kevin Alamsyah', 'Laras Ayu', 'Mochammad Rizky', 'Nadia Anindya', 'Oki Setiawan',
        ];

        $created = 0;
        foreach ($staffNames as $i => $name) {
            $divCode = $staffDivisions[$i % count($staffDivisions)];
            $posCode = $divCode.'-STAFF';
            $email = 'employee'.($i + 5).'@hrconnect.local';
            $empNumber = sprintf('EMP-%04d', $i + 6);

            // Tentuin status: 1 resign, 1 terminated, sisanya active
            $status = match ($i) {
                40 => EmployeeStatus::RESIGNED,   // employee45
                44 => EmployeeStatus::TERMINATED, // employee49
                default => EmployeeStatus::ACTIVE,
            };

            $employmentType = match (true) {
                $i >= 38 && $i <= 40 => EmploymentType::CONTRACT,  // 3 kontrak
                $i >= 42 && $i <= 43 => EmploymentType::PROBATION, // 2 probation
                default => EmploymentType::PERMANENT,
            };

            $this->createStaff($email, $name, $divCode, $posCode, $empNumber, $i, $status, $employmentType);
            $created++;
        }

        // 4. Buat FamilyDetail untuk karyawan yang sudah menikah
        $this->createFamilyDetails();

        $this->command?->info(
            "Seeded: 1 Owner + 4 Managers + {$created} Staff = ".($created + 5).' total employees'
        );
    }

    private function createOwner(): void
    {
        $ownerUser = User::firstOrCreate(
            ['email' => 'owner@hrconnect.local'],
            [
                'name' => 'Owner PT DCM',
                'password' => Hash::make('owner12345'),
                'email_verified_at' => now(),
            ]
        );

        if (! $ownerUser->hasRole('super-admin')) {
            $ownerUser->assignRole('super-admin');
        }

        $this->owner = Employee::firstOrCreate(
            ['user_id' => $ownerUser->id],
            [
                'company_id' => $this->company->id,
                'branch_id' => $this->branch->id,
                'division_id' => $this->divisions['HR']->id,
                'position_id' => $this->positions['HR-MGR']->id,
                'employee_number' => 'EMP-0001',
                'full_name' => 'Owner PT DCM',
                'nik' => '3276012345678901',
                'npwp' => '12.345.678.9-012.000',
                'phone' => '08111222333',
                'gender' => Gender::LAKI_LAKI,
                'marital_status' => MaritalStatus::MARRIED,
                'blood_type' => BloodType::O_PLUS,
                'status' => EmployeeStatus::ACTIVE,
                'birth_date' => '1975-03-15',
                'join_date' => '2018-01-01',
                'education_level' => EducationLevel::MASTER,
                'institution_name' => 'Universitas Indonesia',
                'major' => 'Manajemen',
                'graduation_year' => 1999,
                'salary_type' => SalaryType::MONTHLY,
                'employment_type' => EmploymentType::PERMANENT,
                'shift_id' => $this->defaultShift->id,
                'address_detail' => 'Jl. Sudirman No. 123, Jakarta Selatan',
            ]
        );
    }

    private function createManager(
        string $email,
        string $divCode,
        string $posCode,
        string $empNumber,
        string $name
    ): void {
        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        if (! $user->hasRole('manager')) {
            $user->assignRole('manager');
        }

        $employee = Employee::firstOrCreate(
            ['user_id' => $user->id],
            [
                'company_id' => $this->company->id,
                'branch_id' => $this->branch->id,
                'division_id' => $this->divisions[$divCode]->id,
                'position_id' => $this->positions[$posCode]->id,
                'employee_number' => $empNumber,
                'full_name' => $name,
                'nik' => '327601'.sprintf('%010d', random_int(100000, 999999)),
                'npwp' => $this->generateNpwp((int) substr($empNumber, -2)),
                'phone' => '0811'.sprintf('%08d', random_int(10000000, 99999999)),
                'gender' => $divCode === 'HR' || $divCode === 'FIN' ? Gender::PEREMPUAN : Gender::LAKI_LAKI,
                'marital_status' => MaritalStatus::MARRIED,
                'blood_type' => $this->randomBloodType(),
                'status' => EmployeeStatus::ACTIVE,
                'birth_date' => match ($divCode) {
                    'IT' => '1985-06-20',
                    'HR' => '1988-03-12',
                    'FIN' => '1987-11-05',
                    'OPS' => '1986-09-18',
                    default => '1990-01-01',
                },
                'join_date' => match ($divCode) {
                    'IT' => '2019-03-01',
                    'HR' => '2020-01-15',
                    'FIN' => '2019-07-01',
                    'OPS' => '2020-06-01',
                    default => '2020-01-01',
                },
                'education_level' => EducationLevel::BACHELOR,
                'institution_name' => self::UNIVERSITIES[array_rand(self::UNIVERSITIES)],
                'major' => self::MAJORS_BY_DIV[$divCode][array_rand(self::MAJORS_BY_DIV[$divCode])],
                'graduation_year' => 2009 + (int) (($divCode === 'IT' ? 1985 : 1987) % 5),
                'salary_type' => SalaryType::MONTHLY,
                'employment_type' => EmploymentType::PERMANENT,
                'shift_id' => $this->defaultShift->id,
                'address_detail' => 'Jl. Manager No. '.random_int(1, 100).', Jakarta',
            ]
        );

        $this->managers[$divCode] = $employee;
    }

    private function createStaff(
        string $email,
        string $name,
        string $divCode,
        string $posCode,
        string $empNumber,
        int $index,
        EmployeeStatus $status,
        EmploymentType $employmentType,
    ): void {
        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        if (! $user->hasRole('employee')) {
            $user->assignRole('employee');
        }

        // Data bervariasi berdasarkan index
        $gender = $index % 3 === 0 ? Gender::PEREMPUAN : Gender::LAKI_LAKI;
        $maritalStatus = match (true) {
            $index % 5 === 0 && $index % 10 !== 0 => MaritalStatus::MARRIED,
            $index % 11 === 0 => MaritalStatus::DIVORCED,
            $index % 13 === 0 => MaritalStatus::WIDOWED,
            default => MaritalStatus::SINGLE,
        };

        $birthYear = [1988, 1990, 1992, 1993, 1994, 1995, 1996, 1997, 1998, 1999, 2000, 2001][$index % 12];
        $birthMonth = ($index % 12) + 1;
        $birthDay = min(28, ($index * 7 % 28) + 1);

        $university = self::UNIVERSITIES[$index % count(self::UNIVERSITIES)];
        $major = self::MAJORS_BY_DIV[$divCode][$index % count(self::MAJORS_BY_DIV[$divCode])];

        $educationLevel = match (true) {
            $index % 8 === 0 => EducationLevel::DIPLOMA,
            $index % 15 === 0 => EducationLevel::MASTER,
            $index % 20 === 0 => EducationLevel::SMK,
            default => EducationLevel::BACHELOR,
        };

        $gradYear = $educationLevel === EducationLevel::DIPLOMA ? $birthYear + 21 : (
            $educationLevel === EducationLevel::MASTER ? $birthYear + 26 : $birthYear + 23
        );

        // Join date bervariasi: 2021-2026
        $joinYear = 2021 + ($index % 6);
        $joinMonth = ($index * 3 % 12) + 1;
        $joinDay = min(28, ($index * 13 % 28) + 1);

        $salaryType = match (true) {
            $index % 20 === 0 => SalaryType::DAILY,
            default => SalaryType::MONTHLY,
        };

        Employee::firstOrCreate(
            ['user_id' => $user->id],
            [
                'company_id' => $this->company->id,
                'branch_id' => $this->branch->id,
                'division_id' => $this->divisions[$divCode]->id,
                'position_id' => $this->positions[$posCode]->id,
                'employee_number' => $empNumber,
                'full_name' => $name,
                'nik' => '327601'.sprintf('%010d', 100000 + $index * 17),
                'npwp' => $this->generateNpwp($index + 10),
                'phone' => '0812'.sprintf('%08d', 50000000 + $index * 12345),
                'gender' => $gender,
                'marital_status' => $maritalStatus,
                'blood_type' => $this->randomBloodType(),
                'status' => $status,
                'birth_date' => sprintf('%d-%02d-%02d', $birthYear, $birthMonth, $birthDay),
                'join_date' => sprintf('%d-%02d-%02d', $joinYear, $joinMonth, $joinDay),
                'education_level' => $educationLevel,
                'institution_name' => $university,
                'major' => $major,
                'graduation_year' => $gradYear,
                'salary_type' => $salaryType,
                'employment_type' => $employmentType,
                'shift_id' => $this->defaultShift->id,
                'parent_id' => $this->managers[$divCode]?->id ?? null,
                'address_detail' => 'Jl. '.['Merdeka', 'Sudirman', 'Thamrin', 'Gatot Subroto', 'Kemang', 'Fatmawati', 'Palmerah', 'Ciledug'][$index % 8].' No. '.($index + 10).', Jakarta',
                'resign_date' => $status === EmployeeStatus::RESIGNED ? '2026-06-30' : null,
                'termination_reason' => $status === EmployeeStatus::TERMINATED ? 'PHK karena efisiensi' : null,
            ]
        );
    }

    private function createFamilyDetails(): void
    {
        $marriedEmployees = Employee::query()
            ->whereIn('marital_status', [MaritalStatus::MARRIED->value, MaritalStatus::DIVORCED->value, MaritalStatus::WIDOWED->value])
            ->where('id', '!=', $this->owner->id ?? 0)
            ->get();

        foreach ($marriedEmployees as $employee) {
            // Skip kalau sudah ada family detail
            if ($employee->families()->count() > 0) {
                continue;
            }

            // Pasangan (spouse)
            if ($employee->marital_status === MaritalStatus::MARRIED) {
                $spouseName = $employee->gender === Gender::LAKI_LAKI->value
                    ? 'Ny. '.explode(' ', $employee->full_name)[0]
                    : 'Tn. '.explode(' ', $employee->full_name)[0];
                $spouseGender = $employee->gender === Gender::LAKI_LAKI->value
                    ? Gender::PEREMPUAN
                    : Gender::LAKI_LAKI;

                FamilyDetail::firstOrCreate(
                    ['employee_id' => $employee->id, 'relationship' => FamilyRelationship::SPOUSE->value, 'name' => $spouseName],
                    [
                        'gender' => $spouseGender,
                        'nik' => '327601'.sprintf('%010d', 200000 + $employee->id),
                        'birth_date' => now()->subYears(rand(25, 40))->subDays(rand(0, 365))->toDateString(),
                        'job' => ['Ibu Rumah Tangga', 'Pegawai Swasta', 'Guru', 'Dokter', 'Wiraswasta'][rand(0, 4)],
                        'is_emergency' => true,
                    ]
                );
            }

            // Anak (1-3 anak untuk karyawan menikah)
            $childCount = $employee->marital_status === MaritalStatus::MARRIED ? rand(1, 3) : rand(0, 1);
            for ($c = 1; $c <= $childCount; $c++) {
                $childName = ['Ananda', 'Bima', 'Citra', 'Danu', 'Elsa', 'Fikri', 'Gita', 'Hadi', 'Intan', 'Jaya'][rand(0, 9)].' '.explode(' ', $employee->full_name)[1] ?? '';
                $childGender = $c % 2 === 0 ? Gender::PEREMPUAN : Gender::LAKI_LAKI;

                FamilyDetail::firstOrCreate(
                    ['employee_id' => $employee->id, 'relationship' => FamilyRelationship::CHILD->value, 'name' => $childName],
                    [
                        'gender' => $childGender,
                        'nik' => '327601'.sprintf('%010d', 300000 + $employee->id * 10 + $c),
                        'birth_date' => now()->subYears(rand(1, 17))->subDays(rand(0, 365))->toDateString(),
                        'job' => $c <= 2 ? 'Pelajar' : 'Mahasiswa',
                        'is_emergency' => false,
                    ]
                );
            }
        }
    }

    private function generateNpwp(int $seed): string
    {
        $base = 1234567890 + $seed * 11;

        return substr_replace(substr_replace(substr_replace((string) $base, '.', 2, 0), '.', 6, 0), '-', 10, 0).'.000';
    }

    private function randomBloodType(): BloodType
    {
        return self::BLOOD_TYPES[array_rand(self::BLOOD_TYPES)];
    }
}

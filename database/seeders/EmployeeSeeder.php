<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::where('code', 'HRCONNECT')->firstOrFail();
        $branch = Branch::where('company_id', $company->id)->firstOrFail();
        $department = Department::where('code', 'IT')->firstOrFail();
        $position = Position::where('code', 'IT-STAFF')->firstOrFail();

        $user = User::factory()->create([
            'name' => 'Fikih Admin',
            'email' => 'admin@hris.com',
            'password' => Hash::make('password'),
        ]);

        Employee::factory()->create([
            'user_id' => $user->id,
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'department_id' => $department->id,
            'position_id' => $position->id,
            'full_name' => 'Fikih (Super Admin)',
        ]);

        Employee::factory()->count(20)->create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'department_id' => $department->id,
            'position_id' => $position->id,
        ]);

        $this->command->info('✅ 21 Data Karyawan berhasil di-generate!');
    }
}

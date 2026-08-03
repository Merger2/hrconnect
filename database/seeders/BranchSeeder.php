<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Company;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::where('code', 'HRCONNECT')->first();

        if (! $company) {
            $this->command?->warn('BranchSeeder: Company HRCONNECT belum ada, skip.');

            return;
        }

        // Main branch already created by CompanyAndDivisionSeeder
        $mainBranch = Branch::where('company_id', $company->id)->where('is_main', true)->first();

        if ($mainBranch) {
            $this->command?->info('BranchSeeder: Kantor utama sudah ada di "'.$mainBranch->address.'", skip tambahan.');

            return;
        }

        $this->command?->info('BranchSeeder: Tidak ada cabang tambahan (1 kantor saja).');
    }
}

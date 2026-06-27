<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\EmployeeStatus;
use App\Enums\EmploymentType;
use App\Enums\TerminationType;
use App\Exceptions\BusinessRuleException;
use App\Models\Employee;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EmployeeTerminationService
{
    public function __construct(
        protected PayrollCalculatorService $payrollCalculator,
    ) {}

    public function terminate(Employee $employee, TerminationType $type, ?string $reason = null, ?CarbonInterface $date = null): Employee
    {
        $terminationDate = $date ?: now();

        return DB::transaction(function () use ($employee, $type, $reason, $terminationDate) {
            $lockedEmployee = Employee::lockForUpdate()->findOrFail($employee->id);

            if ($lockedEmployee->status !== EmployeeStatus::ACTIVE) {
                throw new BusinessRuleException('Karyawan sudah tidak aktif. Tidak dapat melakukan terminasi ulang.');
            }

            $updateData = [
                'termination_type' => $type->value,
                'termination_reason' => $reason,
            ];

            match ($type) {
                TerminationType::RESIGN,
                TerminationType::CONTRACT_END => $updateData['status'] = EmployeeStatus::RESIGNED->value,

                TerminationType::DISMISSED => $updateData['status'] = EmployeeStatus::TERMINATED->value,

                TerminationType::DECEASED => $updateData['status'] = EmployeeStatus::DECEASED->value,
            };

            match ($type) {
                TerminationType::RESIGN,
                TerminationType::DISMISSED,
                TerminationType::CONTRACT_END => $updateData['resign_date'] = $terminationDate->toDateString(),

                TerminationType::DECEASED => $updateData['deceased_date'] = $terminationDate->toDateString(),
            };

            $lockedEmployee->update($updateData);

            if ($lockedEmployee->face_embedding || $lockedEmployee->faceDescriptors()->exists()) {
                $lockedEmployee->faceDescriptors()->update(['is_active' => false]);
                $lockedEmployee->forceFill(['face_embedding' => null])->save();
            }

            if ($type !== TerminationType::DECEASED && $lockedEmployee->user) {
                $lockedEmployee->user->delete();
            }

            $lockedEmployee->load('position');

            $financialSummary = [];
            if ($type->requiresPesangon()) {
                $financialSummary['pesangon'] = $this->payrollCalculator->calculatePesangon($lockedEmployee);
            }
            if ($type->requiresPenghargaan()) {
                $financialSummary['uang_penghargaan_masa_kerja'] = $this->payrollCalculator->calculateUangPenghargaanMasaKerja($lockedEmployee);
            }
            if ($type->requiresUangKompensasi()) {
                $financialSummary['uang_kompensasi'] = $this->payrollCalculator->calculateUangKompensasi($lockedEmployee);
            }
            if ($type->requiresLeaveCashOut()) {
                $financialSummary['leave_cash_out'] = $this->payrollCalculator->calculateLeaveCashOut($lockedEmployee);
            }

            DB::afterCommit(fn () => $this->logTermination(
                $lockedEmployee,
                $type,
                $reason,
                $terminationDate,
                $financialSummary,
            ));

            $result = $lockedEmployee->fresh();
            $result->financial_summary = $financialSummary;

            return $result;
        });
    }

    public function processContractEnd(?CarbonInterface $referenceDate = null): int
    {
        $checkDate = $referenceDate ?: now();
        $expiredEmployees = Employee::query()
            ->where('status', EmployeeStatus::ACTIVE->value)
            ->where('employment_type', EmploymentType::CONTRACT->value)
            ->whereDate('contract_end_date', '<=', $checkDate->toDateString())
            ->get();

        $count = 0;
        foreach ($expiredEmployees as $employee) {
            try {
                $this->terminate($employee, TerminationType::CONTRACT_END);
                $count++;
            } catch (\Exception $e) {
                Log::error("Gagal terminasi kontrak karyawan {$employee->id}: {$e->getMessage()}");
            }
        }

        return $count;
    }

    private function logTermination(Employee $employee, TerminationType $type, ?string $reason, CarbonInterface $date, array $financialSummary = []): void
    {
        activity()
            ->performedOn($employee)
            ->withProperties([
                'termination_type' => $type->value,
                'termination_reason' => $reason,
                'termination_date' => $date->toDateString(),
                'financial_summary' => $financialSummary,
            ])
            ->log("Karyawan {$employee->full_name} di-terminasi: {$type->label()}");
    }
}

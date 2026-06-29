<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AssetStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Asset;
use App\Models\AssetHandover;
use App\Models\Employee;
use Illuminate\Support\Facades\DB;

class AssetService
{
    public function createAsset(array $data): Asset
    {
        return DB::transaction(function () use ($data) {
            return Asset::create([
                'company_id' => $data['company_id'] ?? null,
                'name' => $data['name'],
                'serial_number' => $data['serial_number'],
                'code' => $data['code'] ?? null,
                'category' => $data['category'] ?? null,
                'status' => AssetStatus::AVAILABLE,
            ]);
        });
    }

    public function updateAsset(Asset $asset, array $data): Asset
    {
        $asset->update($data);

        return $asset->fresh();
    }

    public function deleteAsset(Asset $asset): void
    {
        if ($asset->status === AssetStatus::ASSIGNED) {
            throw new BusinessRuleException('Aset yang sedang digunakan tidak dapat dihapus.');
        }

        $asset->delete();
    }

    public function handover(Asset $asset, Employee $employee, array $data): AssetHandover
    {
        if ($asset->status !== AssetStatus::AVAILABLE) {
            throw new BusinessRuleException('Aset tidak tersedia untuk diserahkan.');
        }

        return DB::transaction(function () use ($asset, $employee, $data) {
            $handover = AssetHandover::create([
                'asset_id' => $asset->id,
                'employee_id' => $employee->id,
                'handover_date' => $data['handover_date'],
                'return_date' => $data['return_date'] ?? null,
                'condition' => $data['condition'],
                'category' => $data['category'] ?? 'asset',
            ]);

            $asset->update([
                'status' => AssetStatus::ASSIGNED,
                'is_available' => false,
            ]);

            return $handover;
        });
    }

    public function returnAsset(AssetHandover $handover, array $data): AssetHandover
    {
        return DB::transaction(function () use ($handover, $data) {
            $handover->update([
                'return_date' => $data['return_date'] ?? now()->toDateString(),
                'condition' => $data['condition'] ?? $handover->condition,
            ]);

            $handover->asset->update([
                'status' => AssetStatus::AVAILABLE,
                'is_available' => true,
            ]);

            return $handover->fresh(['asset', 'employee:id,employee_number,full_name']);
        });
    }
}

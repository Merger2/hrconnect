<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeePiiResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_number' => $this->employee_number,
            'full_name' => $this->full_name,
            'phone' => $this->maskPhone($this->phone),
            'nik' => $this->maskPii($this->nik),
            'npwp' => $this->maskPii($this->npwp),
            'bank_name' => $this->bank_name,
            'bank_account_number' => $this->maskBankAccount($this->bank_account_number),
            'address_detail' => $this->address_detail,
        ];
    }

    // Masking PII (temuan K4 AUDIT-2026-08-04) — pola sama dengan ProfileResource.
    private function maskPhone(?string $phone): ?string
    {
        if (! $phone) {
            return null;
        }

        $length = strlen($phone);
        if ($length <= 4) {
            return $phone;
        }

        return substr($phone, 0, 4).str_repeat('*', max(0, $length - 8)).substr($phone, -4);
    }

    private function maskBankAccount(?string $account): ?string
    {
        if (! $account) {
            return null;
        }

        $length = strlen($account);
        if ($length <= 4) {
            return $account;
        }

        return str_repeat('*', $length - 4).substr($account, -4);
    }

    private function maskPii(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        $length = strlen($value);
        if ($length <= 4) {
            return $value;
        }

        return substr($value, 0, 2).str_repeat('*', max(0, $length - 4)).substr($value, -2);
    }
}

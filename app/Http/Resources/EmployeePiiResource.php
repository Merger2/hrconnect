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
            'phone' => $this->phone,
            'nik' => $this->nik,
            'npwp' => $this->npwp,
            'bank_name' => $this->bank_name,
            'bank_account_number' => $this->bank_account_number,
        ];
    }
}

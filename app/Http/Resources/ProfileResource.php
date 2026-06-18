<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $request->user()?->id,
            'employee_number' => $this->employee_number,
            'full_name' => $this->full_name,
            'email' => $request->user()?->email,
            'phone' => $this->maskPhone($this->phone),
            'join_date' => $this->join_date?->toDateString(),
            'employment_type' => $this->employment_type?->value,
            'status' => $this->status?->value,
            'marital_status' => $this->marital_status?->value,
            'gender' => $this->gender?->value,
            'blood_type' => $this->blood_type?->value,
            'branch' => BranchResource::make($this->whenLoaded('branch')),
            'department' => DepartmentResource::make($this->whenLoaded('department')),
            'position' => PositionResource::make($this->whenLoaded('position')),
            'shift' => $this->whenLoaded('shift', fn () => $this->shift ? [
                'id' => $this->shift->id,
                'name' => $this->shift->name,
            ] : null),
            'manager' => $this->whenLoaded('manager', fn () => $this->manager ? [
                'id' => $this->manager->id,
                'full_name' => $this->manager->full_name,
            ] : null),
            'face_registered' => ! empty($this->resource->getRawOriginal('face_embedding')),
            'pin_set' => ! empty($this->pin),
            'bank_name' => $this->bank_name,
            'bank_account_number' => $this->maskBankAccount($this->bank_account_number),
        ];
    }

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
}

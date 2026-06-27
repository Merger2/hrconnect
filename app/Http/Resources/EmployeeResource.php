<?php

namespace App\Http\Resources;

use App\Services\FaceRecognitionService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'employee_number' => $this->employee_number,
            'full_name' => $this->full_name,
            'email' => $this->whenLoaded('user', fn () => $this->user?->email),
            'bank_name' => $this->bank_name,
            'gender' => $this->gender?->value,
            'marital_status' => $this->marital_status?->value,
            'blood_type' => $this->blood_type?->value,
            'education_level' => $this->education_level?->value,
            'birth_date' => $this->birth_date?->toDateString(),
            'join_date' => $this->join_date?->toDateString(),
            'status' => $this->status?->value,
            'employment_type' => $this->employment_type?->value,
            'salary_type' => $this->salary_type?->value,
            'address_detail' => $this->address_detail,
            'face_registered' => app(FaceRecognitionService::class)->hasFaceEnrolled($this->resource),
            'pin_set' => ! empty($this->pin),
            'position' => $this->whenLoaded('position', fn () => $this->position ? [
                'id' => $this->position->id,
                'name' => $this->position->name,
                'grade' => $this->position->grade,
                'basic_salary' => $this->position->basic_salary,
            ] : null),
            'department' => DepartmentResource::make($this->whenLoaded('department')),
            'branch' => BranchResource::make($this->whenLoaded('branch')),
            'shift' => $this->whenLoaded('shift', fn () => $this->shift ? [
                'id' => $this->shift->id,
                'name' => $this->shift->name,
            ] : null),
            'manager' => $this->whenLoaded('manager', fn () => $this->manager ? [
                'id' => $this->manager->id,
                'full_name' => $this->manager->full_name,
            ] : null),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}

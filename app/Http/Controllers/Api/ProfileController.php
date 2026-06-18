<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ChangePasswordRequest;
use App\Http\Requests\Api\UpdateProfileRequest;
use Dedoc\Scramble\Attributes\BodyParameter;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

#[Group('Profile')]
class ProfileController extends Controller
{
    #[Endpoint(title: 'Get Profile', description: 'Get authenticated employee profile with masked PII fields. Flow: Profile (read).')]
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $employee = $user->employee()->with([
            'branch:id,name',
            'department:id,name',
            'position:id,name,grade',
            'shift:id,name',
            'manager:id,full_name',
        ])->first();

        if (! $employee) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akun Anda belum terhubung dengan data karyawan.',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $this->formatEmployee($employee, $user),
        ]);
    }

    #[Endpoint(title: 'Update Profile', description: 'Update self-service personal data (phone, address, bank). Flow: Profile (update).')]
    #[BodyParameter(name: 'phone', description: 'Phone number (+62 format)', required: false, type: 'string')]
    #[BodyParameter(name: 'address_detail', description: 'Address detail', required: false, type: 'string')]
    #[BodyParameter(name: 'bank_name', description: 'Bank name', required: false, type: 'string')]
    #[BodyParameter(name: 'bank_account_number', description: 'Bank account number (8-18 digits)', required: false, type: 'string')]
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $employee = $request->user()->employee;

        if (! $employee) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akun Anda belum terhubung dengan data karyawan.',
            ], 404);
        }

        $data = $request->validated();
        $profileData = [];

        foreach (['phone', 'address_detail', 'bank_name', 'bank_account_number'] as $field) {
            if (array_key_exists($field, $data)) {
                $profileData[$field] = $data[$field];
            }
        }

        $employee->update($profileData);

        return response()->json([
            'status' => 'success',
            'message' => 'Profil berhasil diperbarui',
            'data' => $this->formatEmployee($employee->fresh()->load([
                'branch:id,name',
                'department:id,name',
                'position:id,name,grade',
                'shift:id,name',
                'manager:id,full_name',
            ]), $request->user()),
        ]);
    }

    #[Endpoint(title: 'Change Password', description: 'Change authenticated user password. Token stays valid. Flow: Profile (security).')]
    #[BodyParameter(name: 'current_password', description: 'Current password for verification', required: true, type: 'string')]
    #[BodyParameter(name: 'password', description: 'New password (min 8 chars, mixed case, numbers)', required: true, type: 'string')]
    #[BodyParameter(name: 'password_confirmation', description: 'Confirm new password', required: true, type: 'string')]
    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $data = $request->validated();

        if (! Hash::check($data['current_password'], $request->user()->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Password saat ini salah.'],
            ]);
        }

        $request->user()->forceFill([
            'password' => Hash::make($data['password']),
            'password_changed_at' => now(),
        ])->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Password berhasil diubah. Token lama tetap valid.',
        ]);
    }

    private function formatEmployee($employee, $user): array
    {
        return [
            'id' => $employee->id,
            'user_id' => $user->id,
            'employee_number' => $employee->employee_number,
            'full_name' => $employee->full_name,
            'email' => $user->email,
            'phone' => $this->maskPhone($employee->phone),
            'join_date' => $employee->join_date?->toDateString(),
            'employment_type' => $employee->employment_type?->value,
            'status' => $employee->status?->value,
            'marital_status' => $employee->marital_status?->value,
            'gender' => $employee->gender?->value,
            'blood_type' => $employee->blood_type?->value,
            'branch' => $employee->branch ? [
                'id' => $employee->branch->id,
                'name' => $employee->branch->name,
            ] : null,
            'department' => $employee->department ? [
                'id' => $employee->department->id,
                'name' => $employee->department->name,
            ] : null,
            'position' => $employee->position ? [
                'id' => $employee->position->id,
                'name' => $employee->position->name,
                'grade' => $employee->position->grade,
            ] : null,
            'shift' => $employee->shift ? [
                'id' => $employee->shift->id,
                'name' => $employee->shift->name,
            ] : null,
            'manager' => $employee->manager ? [
                'id' => $employee->manager->id,
                'full_name' => $employee->manager->full_name,
            ] : null,
            'face_registered' => ! empty($employee->getRawOriginal('face_embedding')),
            'pin_set' => ! empty($employee->pin),
            'bank_name' => $employee->bank_name,
            'bank_account_number' => $this->maskBankAccount($employee->bank_account_number),
        ];
    }

    private function maskPhone(?string $phone): ?string
    {
        if (! $phone) {
            return null;
        }

        $len = strlen($phone);
        if ($len <= 4) {
            return $phone;
        }

        return substr($phone, 0, 4).str_repeat('*', max(0, $len - 8)).substr($phone, -4);
    }

    private function maskBankAccount(?string $account): ?string
    {
        if (! $account) {
            return null;
        }

        $len = strlen($account);
        if ($len <= 4) {
            return $account;
        }

        return str_repeat('*', $len - 4).substr($account, -4);
    }
}

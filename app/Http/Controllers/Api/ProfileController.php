<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

/**
 * ProfileController — self-service profile untuk Employee.
 *
 * Field employment (position, branch, dept, salary) HANYA HR Manager yang boleh
 * edit via EmployeeController. Endpoint ini fokus self-update personal data.
 *
 * PII fields (phone, nik, npwp, bank_account_number) di-display ter-mask
 * sesuai SEC-1 (kecuali user request export profile).
 */
class ProfileController extends Controller
{
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

    public function update(Request $request): JsonResponse
    {
        $employee = $request->user()->employee;

        if (! $employee) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akun Anda belum terhubung dengan data karyawan.',
            ], 404);
        }

        $data = $request->validate([
            'phone' => ['nullable', 'regex:/^(\+62|0)\d{9,12}$/'],
            'address_detail' => ['nullable', 'string', 'max:500'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'bank_account_number' => ['nullable', 'regex:/^\d{8,18}$/'],
        ]);

        $employee->update($data);

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

    public function changePassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
        ]);

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

        return substr($phone, 0, 4).str_repeat('*', $len - 8).substr($phone, -4);
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

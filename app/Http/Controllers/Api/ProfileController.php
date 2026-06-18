<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ChangePasswordRequest;
use App\Http\Requests\Api\UpdateProfileRequest;
use App\Http\Resources\ProfileResource;
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
            'data' => ProfileResource::make($employee)->resolve($request),
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
            'data' => ProfileResource::make($employee->fresh()->load([
                'branch:id,name',
                'department:id,name',
                'position:id,name,grade',
                'shift:id,name',
                'manager:id,full_name',
            ]))->resolve($request),
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
}

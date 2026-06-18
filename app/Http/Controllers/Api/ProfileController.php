<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ChangePasswordRequest;
use App\Http\Requests\Api\UpdateProfileRequest;
use App\Http\Resources\ProfileResource;
use App\Services\ProfileService;
use Dedoc\Scramble\Attributes\BodyParameter;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

#[Group('Profile')]
class ProfileController extends Controller
{
    public function __construct(
        protected ProfileService $profileService,
    ) {}

    #[Endpoint(title: 'Get Profile', description: 'Get authenticated employee profile with masked PII fields. Flow: Profile (read).')]
    public function show(Request $request): JsonResponse
    {
        $employee = $this->profileService->getProfile($request->user());

        if (! $employee) {
            return $this->employeeNotFound();
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
            return $this->employeeNotFound();
        }

        $this->profileService->updateProfile($employee, $request->only([
            'phone', 'address_detail', 'bank_name', 'bank_account_number',
        ]));

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
        $this->profileService->changePassword(
            $request->user(),
            $request->input('current_password'),
            $request->input('password'),
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Password berhasil diubah. Token lama tetap valid.',
        ]);
    }
}

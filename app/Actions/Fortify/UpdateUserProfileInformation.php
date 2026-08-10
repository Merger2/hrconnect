<?php

namespace App\Actions\Fortify;

use App\Enums\MaritalStatus;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\UpdatesUserProfileInformation;

class UpdateUserProfileInformation implements UpdatesUserProfileInformation
{
    /**
     * Validate and update the given user's profile information.
     *
     * Note: columns like nip, phone, gender, address, provinsi_kode, division_id,
     * job_title_id etc. live on the `employees` table (proxied via $user->employee),
     * so they are persisted there, not on `users`.
     *
     * @param  array<string, mixed>  $input
     */
    public function update(User $user, array $input): void
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'photo' => ['nullable', 'mimes:jpg,jpeg,png', 'max:1024'],
            'nip' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:64'],
            'gender' => ['nullable', 'string', 'in:male,female'],
            'marital_status' => ['nullable', 'string', Rule::in(array_column(MaritalStatus::cases(), 'value'))],
            'address' => ['nullable', 'string', 'max:255'],
            'provinsi_kode' => ['nullable', 'string', 'max:13', 'exists:wilayah,kode'],
            'kabupaten_kode' => ['nullable', 'string', 'max:13', 'exists:wilayah,kode'],
            'kecamatan_kode' => ['nullable', 'string', 'max:13', 'exists:wilayah,kode'],
            'kelurahan_kode' => ['nullable', 'string', 'max:13', 'exists:wilayah,kode'],
            'birth_date' => ['nullable', 'date'],
            'birth_place' => ['nullable', 'string', 'max:255'],
            'education_id' => ['nullable', 'exists:educations,id'],
            'division_id' => ['nullable', 'exists:divisions,id'],
            'job_title_id' => ['nullable', 'exists:job_titles,id'],
        ])->validateWithBag('updateProfileInformation');

        if (isset($input['photo'])) {
            $user->updateProfilePhoto($input['photo']);
        }

        $input = array_map(function ($value) {
            return $value === '' ? null : $value;
        }, $input);

        $userData = ['name' => $input['name']];

        if ($input['email'] !== $user->email && $user instanceof MustVerifyEmail) {
            $this->updateVerifiedUser($user, $input);
        } else {
            $user->forceFill(['name' => $input['name'], 'email' => $input['email']])->save();
        }

        $this->syncEmployeeProfile($user, $input);
    }

    /**
     * Persist employee-owned profile columns on the `employees` table.
     */
    protected function syncEmployeeProfile(User $user, array $input): void
    {
        $employee = $user->employee;

        if (! $employee) {
            // Users without an employee record (e.g. admin-only accounts) cannot
            // persist employee columns — skip silently instead of failing.
            return;
        }

        // State profil sekarang di-mount LENGKAP dari employees (lihat
        // UpdateProfileInformationForm::mount) — null di sini berarti user
        // benar-benar menghapus/reset field, jadi tetap ditulis. Jangan
        // array_filter: itu akan mengunci nilai lama dan membatalkan reset.
        // marital_status fallback 'single' karena kolom NOT NULL (default).
        $employee->update([
            'nip' => $input['nip'] ?? null,
            'phone' => $input['phone'] ?? null,
            'gender' => isset($input['gender']) ? ($input['gender'] === 'male' ? 'L' : 'P') : null,
            'marital_status' => $input['marital_status'] ?? 'single',
            'address_detail' => $input['address'] ?? null,
            'provinsi_kode' => $input['provinsi_kode'] ?? null,
            'kabupaten_kode' => $input['kabupaten_kode'] ?? null,
            'kecamatan_kode' => $input['kecamatan_kode'] ?? null,
            'kelurahan_kode' => $input['kelurahan_kode'] ?? null,
            'birth_date' => $input['birth_date'] ?? null,
            'birth_place' => $input['birth_place'] ?? null,
            'division_id' => $input['division_id'] ?? null,
        ]);
    }

    /**
     * Update the given verified user's profile information.
     *
     * @param  array<string, string>  $input
     */
    protected function updateVerifiedUser(User $user, array $input): void
    {
        $user->forceFill([
            'name' => $input['name'],
            'email' => $input['email'],
            'email_verified_at' => null,
        ])->save();

        $user->sendEmailVerificationNotification();
    }
}

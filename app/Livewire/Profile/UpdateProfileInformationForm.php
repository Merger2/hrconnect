<?php

namespace App\Livewire\Profile;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Laravel\Fortify\Contracts\UpdatesUserProfileInformation;
use Livewire\Component;
use Livewire\WithFileUploads;

class UpdateProfileInformationForm extends Component
{
    use WithFileUploads;

    /**
     * The component's state.
     *
     * @var array
     */
    public $state = [];

    /**
     * The new avatar for the user.
     *
     * @var mixed
     */
    public $photo;

    /**
     * Determine if the verification email was sent.
     *
     * @var bool
     */
    public $verificationLinkSent = false;

    /**
     * Prepare the component.
     *
     * @return void
     */
    public function mount()
    {
        $user = Auth::user();

        $this->state = array_merge([
            'email' => $user->email,
        ], $user->withoutRelations()->toArray());

        // Kolom employee (phone, gender, alamat, wilayah, dll.) tidak ada di
        // tabel users. Expose nilai asli dari employees supaya state mount
        // lengkap — kalau dibiarkan null palsu, save profil akan menimpa
        // kolom NOT NULL (mis. phone) dan selalu gagal 500/rollback.
        $employee = $user->employee;

        if ($employee) {
            // gender/marital_status/birth_date NOT NULL di schema → enum/date
            // cast non-null; `?->` tidak perlu (PHPStan nullsafe.never).
            // education_id TIDAK ada di tabel mana pun (phantom, tidak pernah
            // di-persist oleh UpdateUserProfileInformation) → tidak di-mount.
            // job_title_id ada di positions, bukan employees → baca lewat
            // relasi position agar field menampilkan nilai asli.
            $this->state = array_merge($this->state, [
                'nip' => $employee->nip,
                'phone' => $employee->phone,
                // Gender enum hanya L/P (ekshaustif — PHPStan match.alwaysTrue)
                'gender' => match ($employee->gender->value) {
                    'L' => 'male',
                    'P' => 'female',
                },
                'marital_status' => $employee->marital_status->value,
                'address' => $employee->address_detail,
                'provinsi_kode' => $employee->provinsi_kode,
                'kabupaten_kode' => $employee->kabupaten_kode,
                'kecamatan_kode' => $employee->kecamatan_kode,
                'kelurahan_kode' => $employee->kelurahan_kode,
                'birth_date' => $employee->birth_date->format('Y-m-d'),
                'birth_place' => $employee->birth_place,
                'division_id' => $employee->division_id,
                'job_title_id' => $employee->position?->job_title_id,
            ]);
        }
    }

    /**
     * Update the user's profile information.
     *
     * @return RedirectResponse|null
     */
    public function updateProfileInformation(UpdatesUserProfileInformation $updater)
    {
        $this->resetErrorBag();

        $updater->update(
            Auth::user(),
            $this->photo
                ? array_merge($this->state, ['photo' => $this->photo])
                : $this->state
        );

        if (isset($this->photo)) {
            return redirect()->route('profile.show');
        }

        $this->dispatch('saved');

        $this->dispatch('refresh-navigation-menu');

        return null;
    }

    /**
     * Delete user's profile photo.
     *
     * @return void
     */
    public function deleteProfilePhoto()
    {
        Auth::user()->deleteProfilePhoto();

        $this->dispatch('refresh-navigation-menu');
    }

    /**
     * Sent the email verification.
     *
     * @return void
     */
    public function sendEmailVerification()
    {
        Auth::user()->sendEmailVerificationNotification();

        $this->verificationLinkSent = true;
    }

    /**
     * Get the current user of the application.
     *
     * @return mixed
     */
    public function getUserProperty()
    {
        return Auth::user();
    }

    /**
     * Render the component.
     *
     * @return View
     */
    public function render()
    {
        return view('profile.update-profile-information-form');
    }
}

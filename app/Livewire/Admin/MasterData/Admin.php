<?php

namespace App\Livewire\Admin\MasterData;

use App\Livewire\Forms\UserForm;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;
use Laravel\Jetstream\InteractsWithBanner;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Admin extends Component
{
    use InteractsWithBanner, WithFileUploads, WithPagination;

    public UserForm $form;

    public $groups = [];

    public $deleteName = null;

    public $creating = false;

    public $editing = false;

    public $confirmingDeletion = false;

    public $selectedId = null;

    public $showDetail = null;

    public string $search = '';

    public string $groupFilter = 'all';

    public int $perPage = 20;

    public ?string $credential = null;

    public ?string $credentialConfirmation = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'groupFilter' => ['except' => 'all'],
    ];

    public function __construct()
    {
        $this->groups = User::$groups;
    }

    public function show($id)
    {
        $this->form->setUser($this->findVisibleAdminOrFail($id));
        $this->showDetail = true;
    }

    public function showCreating()
    {
        Gate::authorize('manageUserRecord', [null, 'admin']);
        $this->form->resetErrorBag();
        $this->form->reset();
        $this->creating = true;
        $this->form->group = 'admin';
        // Password WAJIB diketik admin (2026-08-16) — hapus default 'admin'
        // (kredensial lemah). Validasi form (Password::defaults) menolak kosong.
        $this->credential = null;
        $this->credentialConfirmation = null;
    }

    public function create()
    {
        if (auth()->user()?->is_demo) {
            $this->dispatch('error', message: __('Admin accounts cannot be created in demo mode.'));

            return;
        }

        $this->form->password = $this->credential;
        $this->form->password_confirmation = $this->credentialConfirmation;
        $this->form->store();
        $this->credential = null;
        $this->credentialConfirmation = null;
        $this->creating = false;
        $this->banner(__('Created successfully.'));
    }

    public function edit($id)
    {
        $this->form->resetErrorBag();
        $this->form->reset();
        $this->credential = null;
        $this->credentialConfirmation = null;
        $this->editing = true;
        /** @var User $user */
        $user = $this->findVisibleAdminOrFail($id);
        $this->form->setUser($user);
    }

    public function update()
    {
        if (auth()->user()?->is_demo) {
            $this->dispatch('error', message: __('Admin accounts cannot be modified in demo mode.'));

            return;
        }

        $this->form->password = $this->credential;
        $this->form->password_confirmation = $this->credentialConfirmation;
        $this->form->update();
        $this->credential = null;
        $this->credentialConfirmation = null;
        $this->editing = false;
        $this->banner(__('Updated successfully.'));
    }

    public function deleteProfilePhoto()
    {
        if (auth()->user()?->is_demo) {
            $this->dispatch('error', message: __('Admin accounts cannot be modified in demo mode.'));

            return;
        }
        $this->form->deleteProfilePhoto();
    }

    public function confirmDeletion($id)
    {
        $user = $this->findVisibleAdminOrFail($id);
        $this->deleteName = $user->name;
        $this->confirmingDeletion = true;
        $this->selectedId = $user->id;
    }

    public function delete()
    {
        if (auth()->user()?->is_demo) {
            $this->dispatch('error', message: __('Admin accounts cannot be deleted in demo mode.'));

            return;
        }

        $user = $this->findVisibleAdminOrFail($this->selectedId);
        $this->authorizeDeletion($user);
        $this->form->setUser($user)->delete();
        $this->confirmingDeletion = false;
        $this->selectedId = null;
        $this->deleteName = null;
        $this->banner(__('Deleted successfully.'));
    }

    public function canDeleteUser(?User $user): bool
    {
        $actor = auth()->user();

        if (! $actor || ! $user) {
            return false;
        }

        if ($actor->is($user)) {
            return false;
        }

        if ($user->isSuperadmin && ! $actor->canDeleteSuperadminAccounts()) {
            return false;
        }

        return $actor->can('manageUserRecord', [$user, $user->group]);
    }

    public function canCreateAdmin(): bool
    {
        return auth()->user()?->can('manageUserRecord', [null, 'admin']) ?? false;
    }

    public function canCreateSuperadmin(): bool
    {
        return auth()->user()?->can('manageUserRecord', [null, 'superadmin']) ?? false;
    }

    public function canManageUser(?User $user): bool
    {
        return $user !== null
            && (auth()->user()?->can('manageUserRecord', [$user, $user->group]) ?? false);
    }

    public function canViewSuperadminAccounts(): bool
    {
        return auth()->user()?->canViewSuperadminAccounts() ?? false;
    }

    public function canManageSuperadminAccounts(): bool
    {
        return auth()->user()?->canManageSuperadminAccounts() ?? false;
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedGroupFilter()
    {
        $this->resetPage();
    }

    public function updatedPerPage()
    {
        $this->resetPage();
    }

    public function render()
    {
        $actor = auth()->user();

        $users = User::query()
            ->where('group', '!=', 'user')
            ->when(
                ! $this->canViewSuperadminAccounts(),
                fn ($query) => $query->where('group', 'admin')
            )
            ->when(
                filled($this->search),
                fn ($query) => $query->where(function ($subQuery) {
                    $term = '%'.trim($this->search).'%';

                    $subQuery
                        ->where('name', 'like', $term)
                        ->orWhere('email', 'like', $term)
                        ->orWhere('phone', 'like', $term)
                        // nip kolomnya di `employees` — search via relasi
                        // (users.nip tidak ada → 500 undefined column).
                        ->orWhereHas('employee', fn ($employeeQuery) => $employeeQuery->where('nip', 'like', $term))
                        ->orWhere('group', 'like', $term);
                })
            )
            ->when(
                $this->groupFilter !== 'all',
                fn ($query) => $query->where('group', $this->groupFilter)
            )
            ->orderBy('group', 'desc')
            ->orderBy('name')
            ->paginate($this->perPage);

        return view('livewire.admin.master-data.admin', ['users' => $users]);
    }

    private function authorizeDeletion(?User $user): void
    {
        if (! $this->canDeleteUser($user)) {
            throw new AuthorizationException;
        }
    }

    private function findVisibleAdminOrFail($id): User
    {
        $actor = auth()->user();

        return User::query()
            ->whereKey($id)
            ->where('group', '!=', 'user')
            ->when(
                ! $this->canViewSuperadminAccounts(),
                fn ($query) => $query->where('group', 'admin')
            )
            ->firstOrFail();
    }
}

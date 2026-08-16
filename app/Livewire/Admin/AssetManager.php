<?php

namespace App\Livewire\Admin;

use App\Livewire\Forms\CompanyAssetForm;
use App\Models\CompanyAsset;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Laravel\Jetstream\InteractsWithBanner;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class AssetManager extends Component
{
    use InteractsWithBanner;
    use WithPagination;

    public CompanyAssetForm $form;

    public bool $showAssetModal = false;

    public bool $showHistoryModal = false;

    public string $search = '';

    public ?string $typeFilter = null;

    public ?string $statusFilter = null;

    public ?int $selectedCompanyAssetId = null;

    public ?CompanyAsset $viewingHistoryAsset = null;

    public bool $confirmingAssetDeletion = false;

    public function boot(): void
    {
        Gate::authorize('viewAdminAny', CompanyAsset::class);
    }

    #[On('mark-notification-as-read')]
    public function markNotificationAsRead(string $notificationId): void
    {
        $user = auth()->user();
        if ($user) {
            $user->notifications()->find($notificationId)?->markAsRead();
        }
    }

    public function createAsset(): void
    {
        $this->form->reset();
        $this->selectedCompanyAssetId = null;
        $this->showAssetModal = true;
    }

    public function editAsset(CompanyAsset $companyAsset): void
    {
        $this->form->setCompanyAsset($companyAsset);
        $this->selectedCompanyAssetId = $companyAsset->id;
        $this->showAssetModal = true;
    }

    public function saveAsset(): void
    {
        if ($this->selectedCompanyAssetId) {
            $this->form->update();
            $this->banner(__('Asset updated successfully.'));
        } else {
            $this->form->store();
            $this->banner(__('Asset created successfully.'));
        }

        $this->showAssetModal = false;
        $this->reset(['selectedCompanyAssetId']);
    }

    public function confirmAssetDeletion(int $companyAssetId): void
    {
        $this->selectedCompanyAssetId = $companyAssetId;
        $this->confirmingAssetDeletion = true;
    }

    public function deleteAsset(): void
    {
        CompanyAsset::query()->findOrFail($this->selectedCompanyAssetId)->delete();

        $this->confirmingAssetDeletion = false;
        $this->selectedCompanyAssetId = null;
        $this->banner(__('Asset deleted successfully.'));
    }

    public function viewHistory(CompanyAsset $companyAsset): void
    {
        $this->viewingHistoryAsset = $companyAsset->load('histories.user', 'histories.fromEmployee', 'histories.toEmployee');
        $this->showHistoryModal = true;
    }

    public function render()
    {
        $companyAssets = CompanyAsset::query()
            ->with(['user', 'histories'])
            ->when($this->search, function (Builder $query) {
                $query->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('serial_number', 'like', '%'.$this->search.'%')
                    ->orWhere('code', 'like', '%'.$this->search.'%')
                    ->orWhereHas('user', fn (Builder $q) => $q->where('name', 'like', '%'.$this->search.'%'));
            })
            ->when($this->typeFilter, fn (Builder $query) => $query->where('type', $this->typeFilter))
            ->when($this->statusFilter, fn (Builder $query) => $query->where('status', $this->statusFilter))
            ->latest()
            ->paginate(10);

        $types = CompanyAsset::query()->distinct()->pluck('type');
        $statuses = [
            CompanyAsset::STATUS_AVAILABLE,
            CompanyAsset::STATUS_ASSIGNED,
            CompanyAsset::STATUS_MAINTENANCE,
            CompanyAsset::STATUS_LOST,
            CompanyAsset::STATUS_RETIRED,
            CompanyAsset::STATUS_SOLD,
            CompanyAsset::STATUS_AUCTIONED,
            CompanyAsset::STATUS_DISPOSED,
        ];
        $users = User::query()->whereHas('employee')->with('employee')->get();

        return view('livewire.admin.asset-manager', [
            'companyAssets' => $companyAssets,
            'types' => $types,
            'statuses' => $statuses,
            'users' => $users,
            'assetHistories' => $this->viewingHistoryAsset->histories ?? collect(),
            'editMode' => (bool) $this->selectedCompanyAssetId,
        ]);
    }
}

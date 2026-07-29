<?php

namespace App\Livewire\Admin;

use App\Models\Company;
use App\Models\User;
use App\Support\MultiCompanyService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Laravel\Jetstream\InteractsWithBanner;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class CompanyManager extends Component
{
    use InteractsWithBanner;

    protected MultiCompanyService $companies;

    public ?int $editingCompanyId = null;

    public string $name = '';

    public string $segment = '';

    public string $status = Company::STATUS_ACTIVE;

    public string $search = '';

    public string $statusFilter = '';

    public string $selectedCompanyId = '';

    public string $selectedUserId = '';

    public function boot(MultiCompanyService $companies): void
    {
        Gate::authorize('manageCompanies');

        $this->companies = $companies;
    }

    public function create(): void
    {
        $this->resetEditor();
    }

    public function edit(int $companyId): void
    {
        $company = Company::query()->findOrFail($companyId);

        $this->editingCompanyId = $company->id;
        $this->name = $company->name;
        $this->status = $company->status;
        $this->segment = (string) data_get($company->metadata, 'segment', '');
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:160'],
            'segment' => ['nullable', 'string', 'max:80'],
            'status' => ['required', Rule::in([Company::STATUS_ACTIVE, Company::STATUS_SUSPENDED])],
        ]);

        $metadata = array_filter([
            'segment' => $validated['segment'] ?: null,
        ], fn ($value): bool => $value !== null && $value !== '');

        if ($this->editingCompanyId !== null) {
            $company = Company::query()->findOrFail($this->editingCompanyId);
            $this->companies->updateCompany($company, $validated['name'], $validated['status'], $metadata);
            $this->banner(__('Company updated successfully.'));
        } else {
            $this->companies->createCompany($validated['name'], metadata: $metadata);
            $this->banner(__('Company created successfully.'));
        }

        $this->resetEditor();
    }

    public function updateStatus(int $companyId, string $status): void
    {
        $validated = validator(
            ['status' => $status],
            ['status' => ['required', Rule::in([Company::STATUS_ACTIVE, Company::STATUS_SUSPENDED])]],
        )->validate();

        $company = Company::query()->findOrFail($companyId);
        $this->companies->updateCompany(
            $company,
            $company->name,
            $validated['status'],
            $company->metadata ?? [],
        );

        $this->banner(__('Company status updated.'));
    }

    public function assignUser(): void
    {
        $validated = $this->validate([
            'selectedCompanyId' => ['required', 'numeric', Rule::exists('companies', 'id')],
            'selectedUserId' => [
                'required',
                'string',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('group', '!=', 'superadmin')),
            ],
        ]);

        $company = Company::query()->findOrFail((int) $validated['selectedCompanyId']);
        $user = User::query()->where('group', '!=', 'superadmin')->findOrFail($validated['selectedUserId']);

        $this->companies->assignUser($user, $company);

        $this->selectedUserId = '';
        $this->banner(__('User assigned to company.'));
    }

    public function unassignUser(string $userId): void
    {
        $user = User::query()->where('group', '!=', 'superadmin')->findOrFail($userId);
        $this->companies->unassignUser($user);

        $this->banner(__('User removed from company scope.'));
    }

    public function render()
    {
        $companies = Company::query()
            ->with(['users' => fn ($query) => $query->select('id', 'company_id', 'name', 'email', 'group')->orderBy('name')])
            ->withCount('users')
            ->when($this->search !== '', function (Builder $query): void {
                $search = '%'.strtolower($this->search).'%';

                $query->where(function (Builder $nested) use ($search): void {
                    $nested
                        ->whereRaw('LOWER(name) LIKE ?', [$search])
                        ->orWhereRaw('LOWER(slug) LIKE ?', [$search]);
                });
            })
            ->when($this->statusFilter !== '', fn (Builder $query) => $query->where('status', $this->statusFilter))
            ->orderBy('name')
            ->get();

        return view('livewire.admin.company-manager', [
            'companies' => $companies,
            'assignableUsers' => User::query()
                ->where('group', '!=', 'superadmin')
                ->with('company:id,name')
                ->orderBy('name')
                ->get(['id', 'company_id', 'name', 'email', 'group']),
        ]);
    }

    private function resetEditor(): void
    {
        $this->resetErrorBag();
        $this->editingCompanyId = null;
        $this->name = '';
        $this->segment = '';
        $this->status = Company::STATUS_ACTIVE;
    }
}

<?php

declare(strict_types=1);

namespace App\Livewire\Admin\ImportExport;

use App\Exports\UserImportTemplateExport;
use App\Models\ImportExportRun;
use App\Models\User;
use App\Support\ImportExportRunService;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Component as LivewireComponent;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

// Q1: #[Component('admin.import-export.user')] dihapus — atribut
// `Livewire\Attributes\Component` TIDAK ADA di Livewire 4; nama komponen
// resolve otomatis dari namespace (UserImportExport → admin.import-export.user).
#[Layout('layouts.app')]
final class UserImportExport extends LivewireComponent
{
    use AuthorizesRequests;
    use WithFileUploads;

    public array $groups = ['user'];

    public bool $previewing = false;

    public $file = null;

    public array $importResult = [];

    public array $importErrors = [];

    public function mount(): void
    {
        $this->groups = ['user'];
    }

    public function render(): View
    {
        $this->authorize('viewUserImportExport');

        $users = $this->previewing
            ? User::query()->whereIn('group', $this->groups)->take(10)->get()
            : collect();

        return view('livewire.admin.import-export.user', [
            'users' => $users,
            'recentRuns' => ImportExportRun::query()
                ->where('resource', 'user')
                ->where('requested_by_user_id', auth()->id())
                ->latest()
                ->take(5)
                ->get(),
        ]);
    }

    public function export(): void
    {
        $this->authorize('exportUsers');

        $validated = $this->validate([
            'groups' => ['required', 'array', 'min:1'],
            'groups.*' => ['string', 'in:user,admin,superadmin'],
        ]);

        $run = app(ImportExportRunService::class)->queueUsersExport(auth()->user(), $validated['groups']);

        $this->dispatch('notify', type: 'success', message: __('User export queued. Track progress from run #:id.', ['id' => $run->id]));

        $this->previewing = false;
    }

    public function import(): void
    {
        $this->authorize('importUsers');

        $this->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
        ]);

        $run = app(ImportExportRunService::class)->queueUsersImport(auth()->user(), $this->file);

        $this->file = null;

        $this->dispatch('notify', type: 'success', message: __('User import queued. Track progress from run #:id.', ['id' => $run->id]));
    }

    public function downloadTemplate(): BinaryFileResponse
    {
        $this->authorize('importUsers');

        // Mock-miss fix (2026-08-16): sebelumnya hanya toast info tanpa file
        // nyata. Kini mengunduh template header yang sesuai kontrak UserImport.
        return Excel::download(new UserImportTemplateExport, 'user-import-template.xlsx');
    }
}

<?php

declare(strict_types=1);

namespace App\Livewire\Admin\ImportExport;

use App\Enums\EducationLevel;
use App\Models\Attendance;
use App\Models\Division;
use App\Models\ImportExportRun;
use App\Models\JobTitle;
use App\Support\ImportExportRunService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Component;
use Livewire\Attributes\Layout;
use Livewire\Component as LivewireComponent;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Component('admin.import-export.attendance')]
#[Layout('layouts.app')]
final class AttendanceImportExport extends LivewireComponent
{
    use AuthorizesRequests;
    use WithFileUploads;
    use WithPagination;

    public string $start_date = '';

    public string $end_date = '';

    public ?string $division = null;

    public ?string $job_title = null;

    public ?string $education = null;

    public bool $previewing = false;

    public string $mode = '';

    public $file = null;

    public array $importResult = [];

    public array $importErrors = [];

    public string $skippedRows = '0';

    public function mount(): void
    {
        $this->start_date = now()->startOfMonth()->format('Y-m-d');
        $this->end_date = now()->format('Y-m-d');
    }

    public function render(): View
    {
        $this->authorize('viewAttendanceImportExport');

        $attendances = $this->previewing && $this->start_date && $this->end_date
            ? $this->previewQuery()->take(10)->get()
            : collect();

        return view('livewire.admin.import-export.attendance', [
            'attendances' => $attendances,
            'divisions' => Division::orderBy('name')->get(['id', 'name']),
            'jobTitles' => JobTitle::orderBy('name')->get(['id', 'name']),
            'educations' => collect(EducationLevel::cases())->map(fn (EducationLevel $level) => (object) [
                'id' => $level->value,
                'name' => $level->label(),
            ]),
            'recentRuns' => ImportExportRun::query()
                ->where('resource', 'attendance')
                ->where('requested_by_user_id', auth()->id())
                ->latest()
                ->take(5)
                ->get(),
            'importResult' => $this->importResult,
        ]);
    }

    public function preview(): void
    {
        $this->validate(['start_date' => 'required|date', 'end_date' => 'required|date|after_or_equal:start_date']);

        $this->previewing = true;
        $this->mode = 'export';
    }

    public function export(): void
    {
        $this->authorize('exportAttendances');

        $validated = $this->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'division' => ['nullable', 'integer'],
            'job_title' => ['nullable', 'integer'],
            'education' => ['nullable', Rule::in(array_column(EducationLevel::cases(), 'value'))],
        ]);

        $run = app(ImportExportRunService::class)->queueAttendanceExport(auth()->user(), [
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'division' => $validated['division'] ?? null,
            'job_title' => $validated['job_title'] ?? null,
            'education' => $validated['education'] ?? null,
        ]);

        $this->dispatch('notify', type: 'success', message: __('Attendance export queued. Track progress from run #:id.', ['id' => $run->id]));
        $this->previewing = false;
    }

    public function import(): void
    {
        $this->authorize('importAttendances');

        $this->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
        ]);

        $run = app(ImportExportRunService::class)->queueAttendanceImport(auth()->user(), $this->file);

        $this->file = null;

        $this->dispatch('notify', type: 'success', message: __('Attendance import queued. Track progress from run #:id.', ['id' => $run->id]));
    }

    public function downloadTemplate(): void
    {
        $this->authorize('importAttendances');

        $this->dispatch('notify', type: 'info', message: __('Download the template from the import section.'));
    }

    protected function previewQuery(): Builder
    {
        return Attendance::query()
            ->with(['user', 'shift'])
            ->whereBetween('date', [$this->start_date, $this->end_date])
            ->when($this->division, fn ($q, $v) => $q->whereHas('user.employee', fn ($q) => $q->where('division_id', $v)))
            ->when($this->job_title, fn ($q, $v) => $q->whereHas('user.employee', fn ($q) => $q->where('job_title_id', $v)))
            ->when($this->education, fn ($q, $v) => $q->whereHas('user.employee', fn ($q) => $q->where('education_level', $v)))
            ->orderBy('date', 'desc');
    }
}

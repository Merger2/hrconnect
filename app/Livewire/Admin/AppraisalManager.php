<?php

namespace App\Livewire\Admin;

use App\Livewire\Forms\AppraisalForm;
use App\Models\Appraisal;
use App\Models\AppraisalEvaluation;
use App\Models\Employee;
use App\Models\KpiTemplate;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Laravel\Jetstream\InteractsWithBanner;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class AppraisalManager extends Component
{
    use InteractsWithBanner;
    use WithPagination;

    public AppraisalForm $form;

    public bool $showAppraisalModal = false;

    public string $search = '';

    public ?string $periodFilter = null;

    public ?string $statusFilter = null;

    public ?int $selectedAppraisalId = null;

    public bool $confirmingAppraisalDeletion = false;

    // --- Modal properties ---
    public ?User $evaluatingUser = null;

    public ?Appraisal $activeAppraisal = null;

    public ?int $activeAppraisalId = null;

    public string $appraisalStatus = 'draft';

    public float $attendanceScore = 0.0;

    public array $managerScores = []; // [evaluation_id => score]

    public array $evidenceDescriptions = []; // [evaluation_id => text]

    public ?string $employeeNotes = null;

    public ?string $generalNotes = null;

    public ?string $developmentRecommendations = null;

    public ?string $meetingDate = null;

    public ?string $meetingLink = null;

    // --- Filter properties from blade ---
    public int $month = 0;

    public int $year = 0;

    public Collection $months;

    public Collection $years;

    public bool $periodOpen = true;

    public ?string $periodLabel = null;

    public array $bellCurve = [
        'A' => 0, 'B' => 0, 'C' => 0, 'D' => 0, 'E' => 0,
    ];

    public function mount(): void
    {
        $this->months = collect(range(1, 12))->mapWithKeys(fn ($m) => [$m => Carbon::create(null, $m)->translatedFormat('F')]);
        $this->years = collect(range(Carbon::now()->year - 2, Carbon::now()->year + 1))->mapWithKeys(fn ($y) => [$y => $y]);
        $this->month = Carbon::now()->month;
        $this->year = Carbon::now()->year;
        $this->initializeAppraisalPeriod();
    }

    public function boot(): void
    {
        Gate::authorize('viewAdminAny', Appraisal::class);
    }

    public function updatedMonth(): void
    {
        $this->initializeAppraisalPeriod();
    }

    public function updatedYear(): void
    {
        $this->initializeAppraisalPeriod();
    }

    protected function initializeAppraisalPeriod(): void
    {
        $appraisalsInPeriod = Appraisal::query()
            ->where('period', $this->year.'-'.$this->month)
            ->get();

        // Calculate bell curve for the period
        $this->bellCurve = [
            'A' => $appraisalsInPeriod->filter(fn ($a) => $a->final_score >= 90)->count(),
            'B' => $appraisalsInPeriod->filter(fn ($a) => $a->final_score >= 80 && $a->final_score < 90)->count(),
            'C' => $appraisalsInPeriod->filter(fn ($a) => $a->final_score >= 70 && $a->final_score < 80)->count(),
            'D' => $appraisalsInPeriod->filter(fn ($a) => $a->final_score >= 60 && $a->final_score < 70)->count(),
            'E' => $appraisalsInPeriod->filter(fn ($a) => $a->final_score < 60)->count(),
        ];

        // This logic simulates the period lock, replace with actual setting
        $this->periodOpen = true; // Setting::getValue('appraisal.period_lock', false) === false;
        $this->periodLabel = Carbon::create($this->year, $this->month)->translatedFormat('F Y');
    }

    public function createAppraisal(): void
    {
        $this->form->reset();
        $this->selectedAppraisalId = null;
        $this->showAppraisalModal = true;
    }

    public function editAppraisal(Appraisal $appraisal): void
    {
        $this->form->setAppraisal($appraisal);
        $this->selectedAppraisalId = $appraisal->id;
        $this->showAppraisalModal = true;
    }

    public function saveAppraisal(): void
    {
        if ($this->selectedAppraisalId) {
            $this->form->update();
            $this->banner(__('Appraisal updated successfully.'));
        } else {
            $this->form->store();
            $this->banner(__('Appraisal created successfully.'));
        }

        $this->showAppraisalModal = false;
        $this->reset(['selectedAppraisalId']);
        $this->initializeAppraisalPeriod(); // Refresh bell curve and list
    }

    public function confirmAppraisalDeletion(int $appraisalId): void
    {
        $this->selectedAppraisalId = $appraisalId;
        $this->confirmingAppraisalDeletion = true;
    }

    public function deleteAppraisal(): void
    {
        Appraisal::query()->findOrFail($this->selectedAppraisalId)->delete();

        $this->confirmingAppraisalDeletion = false;
        $this->selectedAppraisalId = null;
        $this->banner(__('Appraisal deleted successfully.'));
        $this->initializeAppraisalPeriod(); // Refresh bell curve and list
    }

    public function initOrEvaluate(int $userId): void
    {
        $this->evaluatingUser = User::with('employee')->findOrFail($userId);
        $this->activeAppraisal = Appraisal::query()
            ->with(['employee.user', 'evaluations.kpiTemplate.kpiGroup'])
            ->where('employee_id', $this->evaluatingUser->employee->id)
            ->where('period', $this->year.'-'.$this->month)
            ->first();

        if ($this->activeAppraisal) {
            $this->activeAppraisalId = $this->activeAppraisal->id;
            $this->appraisalStatus = $this->activeAppraisal->status;
            $this->employeeNotes = $this->activeAppraisal->notes;
            $this->generalNotes = $this->activeAppraisal->recommendations; // assuming recommendations is general notes
            $this->developmentRecommendations = $this->activeAppraisal->recommendations; // can be split later

            foreach ($this->activeAppraisal->evaluations as $evaluation) {
                $this->managerScores[$evaluation->kpi_template_id] = $evaluation->manager_score; // Use kpi_template_id as key
                $this->evidenceDescriptions[$evaluation->kpi_template_id] = $evaluation->comments; // Use kpi_template_id as key
            }
            $this->attendanceScore = 75.0; // dummy, replace with actual logic
            $this->meetingDate = $this->activeAppraisal->meeting_date?->format('Y-m-d');
            $this->meetingLink = null; // assuming no link in DB
        } else {
            $this->activeAppraisalId = null;
            $this->appraisalStatus = 'draft';
            $this->managerScores = KpiTemplate::query()->where('is_active', true)->pluck('id')->mapWithKeys(fn ($id) => [$id => null])->toArray();
            $this->evidenceDescriptions = KpiTemplate::query()->where('is_active', true)->pluck('id')->mapWithKeys(fn ($id) => [$id => null])->toArray();
            $this->attendanceScore = 75.0; // dummy
            $this->employeeNotes = null;
            $this->generalNotes = null;
            $this->developmentRecommendations = null;
            $this->meetingDate = null;
            $this->meetingLink = null;
        }

        $this->showAppraisalModal = true;
    }

    public function saveEvaluation(): void
    {
        $this->validate([
            'appraisalStatus' => 'required|string',
            'managerScores.*' => 'nullable|integer|min:1|max:5',
            'evidenceDescriptions.*' => 'nullable|string|max:1000',
            'employeeNotes' => 'nullable|string|max:2000',
            'generalNotes' => 'nullable|string|max:2000',
            'developmentRecommendations' => 'nullable|string|max:2000',
            'meetingDate' => 'nullable|date',
            'meetingLink' => 'nullable|url|max:255',
        ]);

        if (! $this->evaluatingUser || ! $this->evaluatingUser->employee) {
            $this->dangerBanner(__('Employee not found.'));

            return;
        }

        $totalScore = $this->calculateTotalScore();

        if (! $this->activeAppraisal) {
            $this->activeAppraisal = Appraisal::create([
                'employee_id' => $this->evaluatingUser->employee->id,
                'reviewer_id' => auth()->id(),
                'period' => $this->year.'-'.$this->month,
                'review_date' => now(),
                'status' => $this->appraisalStatus,
                'final_score' => $totalScore,
                'notes' => $this->employeeNotes,
                'recommendations' => $this->generalNotes,
                'meeting_date' => $this->meetingDate,
            ]);

            // Create evaluations based on active KPI templates
            $kpiTemplates = KpiTemplate::query()->where('is_active', true)->get();
            foreach ($kpiTemplates as $template) {
                AppraisalEvaluation::create([
                    'appraisal_id' => $this->activeAppraisal->id,
                    'kpi_template_id' => $template->id,
                    'self_score' => null,
                    'manager_score' => $this->managerScores[$template->id] ?? null,
                    'comments' => $this->evidenceDescriptions[$template->id] ?? null,
                ]);
            }
        } else {
            $this->activeAppraisal->update([
                'status' => $this->appraisalStatus,
                'final_score' => $totalScore,
                'notes' => $this->employeeNotes,
                'recommendations' => $this->generalNotes,
                'meeting_date' => $this->meetingDate,
            ]);

            foreach ($this->activeAppraisal->evaluations as $evaluation) {
                $evaluation->update([
                    'manager_score' => $this->managerScores[$evaluation->kpi_template_id] ?? null, // Use kpi_template_id
                    'comments' => $this->evidenceDescriptions[$evaluation->kpi_template_id] ?? null, // Use kpi_template_id
                ]);
            }
        }

        $this->showAppraisalModal = false;
        $this->reset(['evaluatingUser', 'activeAppraisal', 'activeAppraisalId']);
        $this->banner(__('Appraisal saved successfully.'));
        $this->initializeAppraisalPeriod(); // Refresh bell curve and list
    }

    protected function calculateTotalScore(): float
    {
        $kpiTemplates = KpiTemplate::query()->where('is_active', true)->with('kpiGroup')->get();
        $totalKpiScore = 0;
        $totalKpiWeight = 0;

        foreach ($kpiTemplates as $template) {
            $kpiGroup = $template->kpiGroup;
            $managerScore = $this->managerScores[$template->id] ?? 0;
            $kpiWeight = $template->weight / 100; // KPI's own weight
            $groupWeight = $kpiGroup?->weight / 100 ?? 1; // Group weight

            $totalKpiScore += ($managerScore * $kpiWeight * $groupWeight);
            $totalKpiWeight += ($kpiWeight * $groupWeight);
        }

        $attendanceWeight = (float) Setting::getValue('appraisal.attendance_weight', 0.3); // 30%
        $kpiComponentWeight = 1 - $attendanceWeight;

        $weightedKpiScore = ($totalKpiWeight > 0) ? ($totalKpiScore / $totalKpiWeight) : 0;

        return ($this->attendanceScore * $attendanceWeight) + ($weightedKpiScore * $kpiComponentWeight);
    }

    public function calibrate(int $appraisalId, string $status): void
    {
        Gate::authorize('calibrate', Appraisal::query()->findOrFail($appraisalId));

        $appraisal = Appraisal::query()->findOrFail($appraisalId);
        $appraisal->update(['calibration_status' => $status]);

        $this->banner(__('Appraisal calibration status updated.'));
    }

    public function render()
    {
        $appraisals = Appraisal::query()
            ->with(['employee.user', 'employee.division', 'reviewer', 'evaluator', 'calibrator'])
            ->when($this->search, fn (Builder $query) => $query->where('period', 'like', '%'.$this->search.'%')
                ->orWhereHas('employee.user', fn (Builder $q) => $q->where('name', 'like', '%'.$this->search.'%')))
            ->when($this->periodFilter, fn (Builder $query) => $query->where('period', $this->periodFilter))
            ->when($this->statusFilter, fn (Builder $query) => $query->where('status', $this->statusFilter))
            ->latest()
            ->paginate(10);

        $employees = Employee::query()->with('user')->get()->map(fn ($employee) => ['id' => $employee->user->id, 'name' => $employee->user->name])->sortBy('name');
        // Original blade was looping $users, not $employees. Let's make it consistent.
        // $users = User::query()->get(['id', 'name'])->sortBy('name');
        $periods = Appraisal::query()->distinct()->pluck('period');
        $statuses = ['draft', 'self_assessment', 'manager_review', '1on1_scheduled', 'completed']; // Correct statuses

        return view('livewire.admin.appraisal-manager', [
            'appraisals' => $appraisals,
            'employees' => $employees,
            'users' => User::query()->get(['id', 'name']), // Used for modal dropdowns.
            'periods' => $periods,
            'statuses' => $statuses,
            'evaluatingUser' => $this->evaluatingUser,
            'activeAppraisal' => $this->activeAppraisal,
        ]);
    }
}

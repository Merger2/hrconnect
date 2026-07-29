<?php

namespace App\Livewire\User;

use App\Models\Appraisal;
use App\Models\Setting;
use App\Notifications\AppraisalActionNotification;
use App\Services\AppraisalService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class MyPerformance extends Component
{
    use AuthorizesRequests;

    protected AppraisalService $appraisalService;

    public $showSelfAssessmentModal = false;

    public $activeAppraisalId = null;

    // Arrays for binding
    public $selfScores = [];

    public $evidenceDescriptions = [];

    public $employeeNotes = '';

    public $evaluations = [];

    protected $rules = [
        'selfScores.*' => 'required|numeric|min:1|max:5',
        'evidenceDescriptions.*' => 'nullable|string',
        'employeeNotes' => 'nullable|string',
    ];

    public function boot(AppraisalService $appraisalService): void
    {
        $this->appraisalService = $appraisalService;
    }

    public function openSelfAssessment($appraisalId)
    {
        // Check Period Lock
        $periodOpen = (bool) Setting::getValue('appraisal.period_open', false);
        $deadline = Setting::getValue('appraisal.period_deadline', '');
        if (! $periodOpen || ($deadline && now()->gt($deadline))) {
            session()->flash('error', __('The appraisal submission window is currently closed. Please contact HR.'));

            return;
        }

        $appraisal = Appraisal::findOrFail($appraisalId);
        $this->authorize('selfAssess', $appraisal);

        // Auto-sync missing KPIs (in case HR added new KPI Groups after this appraisal was drafted)
        $this->appraisalService->initAppraisal(auth()->user(), $appraisal->period_month, $appraisal->period_year);

        // Re-fetch with loaded relations after syncing
        $appraisal = Appraisal::with('evaluations.kpiTemplate.kpiGroup')->find($appraisalId);

        $this->activeAppraisalId = $appraisal->id;
        $this->evaluations = $appraisal->evaluations;

        foreach ($this->evaluations as $evaluation) {
            $this->selfScores[$evaluation->id] = $evaluation->self_score ? ($evaluation->self_score / 20) : '';
            $this->evidenceDescriptions[$evaluation->id] = $evaluation->evidence_description ?? '';
        }
        $this->employeeNotes = $appraisal->employee_notes ?? '';

        $this->showSelfAssessmentModal = true;
    }

    public function submitSelfAssessment()
    {
        $this->validate();

        $appraisal = Appraisal::findOrFail($this->activeAppraisalId);
        $this->authorize('selfAssess', $appraisal);

        foreach ($this->evaluations as $evaluation) {
            $mappedSelfScore = isset($this->selfScores[$evaluation->id]) ? ($this->selfScores[$evaluation->id] * 20) : null;
            $evaluation->update([
                'self_score' => $mappedSelfScore,
                'evidence_description' => $this->evidenceDescriptions[$evaluation->id] ?? null,
            ]);
        }

        $appraisal->update([
            'status' => 'manager_review',
            'employee_notes' => $this->employeeNotes,
        ]);

        $supervisor = auth()->user()->supervisor;
        if ($supervisor) {
            $supervisor->notify(new AppraisalActionNotification(
                $appraisal,
                __(':name has submitted their self-assessment and it is ready for your manager review.', [
                    'name' => auth()->user()->name,
                ]),
                route('admin.appraisals')
            ));
        }

        $this->showSelfAssessmentModal = false;
        session()->flash('success', __('Self-assessment submitted successfully. Waiting for manager review.'));
    }

    public function acknowledge($appraisalId)
    {
        $appraisal = Appraisal::findOrFail($appraisalId);
        $this->authorize('acknowledge', $appraisal);

        $appraisal->update([
            'employee_acknowledgement' => true,
        ]);

        session()->flash('success', __('You have successfully acknowledged your final performance review.'));
    }

    public function render()
    {
        $this->authorize('viewAny', Appraisal::class);

        $appraisals = Appraisal::where('user_id', auth()->id())
            ->with('evaluator:id,name')
            ->orderBy('period_year', 'desc')
            ->orderBy('period_month', 'desc')
            ->get();

        $activeAppraisal = $this->activeAppraisalId
            ? Appraisal::query()
                ->with('evaluations.kpiTemplate.kpiGroup')
                ->where('user_id', auth()->id())
                ->find($this->activeAppraisalId)
            : null;

        return view('livewire.user.my-performance', compact('appraisals', 'activeAppraisal'));
    }
}

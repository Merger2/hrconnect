<?php

namespace App\Livewire\Forms;

use App\Models\Appraisal;
use Livewire\Attributes\Validate;
use Livewire\Form;

class AppraisalForm extends Form
{
    public ?Appraisal $appraisal = null;

    #[Validate('required|integer|exists:employees,id')]
    public ?int $employee_id = null;

    #[Validate('required|integer|exists:users,id')]
    public ?int $reviewer_id = null;

    #[Validate('nullable|integer|exists:users,id')]
    public ?int $evaluator_id = null;

    #[Validate('nullable|integer|exists:users,id')]
    public ?int $calibrator_id = null;

    #[Validate('required|string|max:50')]
    public string $period = '';

    #[Validate('required|date')]
    public ?string $review_date = null;

    #[Validate('nullable|date')]
    public ?string $meeting_date = null;

    #[Validate('nullable|numeric|min:0|max:100')]
    public ?float $final_score = null;

    #[Validate('required|string|max:50')]
    public string $status = 'draft';

    #[Validate('nullable|string|max:1000')]
    public ?string $notes = null;

    #[Validate('boolean')]
    public bool $employee_acknowledgement = false;

    #[Validate('nullable|string|max:1000')]
    public ?string $recommendations = null;

    public function setAppraisal(Appraisal $appraisal)
    {
        $this->appraisal = $appraisal;
        $this->employee_id = $appraisal->employee_id;
        $this->reviewer_id = $appraisal->reviewer_id;
        $this->evaluator_id = $appraisal->evaluator_id;
        $this->calibrator_id = $appraisal->calibrator_id;
        $this->period = $appraisal->period;
        $this->review_date = $appraisal->review_date->format('Y-m-d');
        $this->meeting_date = $appraisal->meeting_date?->format('Y-m-d');
        $this->final_score = $appraisal->final_score;
        $this->status = $appraisal->status;
        $this->notes = $appraisal->notes;
        $this->employee_acknowledgement = $appraisal->employee_acknowledgement;
        $this->recommendations = $appraisal->recommendations;
    }

    public function store(): Appraisal
    {
        $this->validate();

        $appraisal = Appraisal::create($this->all());

        $this->reset();

        return $appraisal;
    }

    public function update(): Appraisal
    {
        $this->validate();

        $this->appraisal->update($this->all());

        return $this->appraisal;
    }
}

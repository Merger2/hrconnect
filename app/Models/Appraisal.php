<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Appraisal extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'reviewer_id',
        'evaluator_id',
        'calibrator_id',
        'period',
        'review_date',
        'meeting_date',
        'final_score',
        'status',
        'notes',
        'employee_acknowledgement',
        'recommendations',
    ];

    protected $casts = [
        'meeting_date' => 'date',
        'employee_acknowledgement' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function evaluator()
    {
        return $this->belongsTo(User::class, 'evaluator_id');
    }

    public function calibrator()
    {
        return $this->belongsTo(User::class, 'calibrator_id');
    }

    public function evaluations()
    {
        return $this->hasMany(AppraisalEvaluation::class);
    }
}

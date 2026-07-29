<?php

namespace App\Events;

use App\Models\Payroll;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PayrollApproved
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Payroll $payroll,
    ) {}
}

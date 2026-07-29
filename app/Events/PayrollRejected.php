<?php

namespace App\Events;

use App\Models\Payroll;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PayrollRejected
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Payroll $payroll,
        public string $reason,
    ) {}
}

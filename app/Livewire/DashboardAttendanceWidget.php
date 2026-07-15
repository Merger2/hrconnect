<?php

declare(strict_types=1);

namespace App\Livewire;

use Livewire\Component;

/**
 * Dashboard attendance widget.
 *
 * Renders the shell; real-time clock status + history are loaded client-side
 * via Alpine.js against the /api/v1/attendance endpoints (same convention as
 * resources/views/attendance/index.blade.php).
 */
class DashboardAttendanceWidget extends Component
{
    public function render()
    {
        return view('livewire.dashboard-attendance-widget');
    }
}

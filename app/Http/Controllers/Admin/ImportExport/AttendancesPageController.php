<?php

namespace App\Http\Controllers\Admin\ImportExport;

use App\Http\Controllers\Controller;
use App\Livewire\Admin\ImportExport\AttendanceComponent;

/**
 * @deprecated Digantikan oleh Livewire component admin.import-export.attendance
 * @see AttendanceComponent
 */
class AttendancesPageController extends Controller
{
    public function __invoke()
    {
        $this->authorize('viewAttendanceImportExport');

        return view('admin.import-export.attendances');
    }
}

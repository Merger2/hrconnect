<?php

namespace App\Http\Controllers\Admin\MasterData;

use App\Http\Controllers\Controller;
use App\Livewire\Admin\MasterData\ShiftComponent;

/**
 * @deprecated Digantikan oleh Livewire component admin.master-data.shift-component
 * @see ShiftComponent
 */
class ShiftController extends Controller
{
    public function __invoke()
    {
        return view('admin.master-data.shift');
    }
}

<?php

namespace App\Http\Controllers\Admin\MasterData;

use App\Http\Controllers\Controller;
use App\Livewire\Admin\MasterData\DivisionComponent;

/**
 * @deprecated Digantikan oleh Livewire component admin.master-data.division-component
 * @see DivisionComponent
 */
class DivisionController extends Controller
{
    public function __invoke()
    {
        return view('admin.master-data.division');
    }
}

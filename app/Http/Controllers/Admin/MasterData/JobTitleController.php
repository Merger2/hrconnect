<?php

namespace App\Http\Controllers\Admin\MasterData;

use App\Http\Controllers\Controller;
use App\Livewire\Admin\MasterData\JobTitleComponent;

/**
 * @deprecated Digantikan oleh Livewire component admin.master-data.job-title-component
 * @see JobTitleComponent
 */
class JobTitleController extends Controller
{
    public function __invoke()
    {
        return view('admin.master-data.job-title');
    }
}

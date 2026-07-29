<?php

namespace App\Http\Controllers\Admin\MasterData;

use App\Http\Controllers\Controller;
use App\Livewire\Admin\MasterData\EducationComponent;
use Illuminate\Support\Facades\Gate;

/**
 * @deprecated Digantikan oleh Livewire component admin.master-data.education-component
 * @see EducationComponent
 */
class EducationController extends Controller
{
    public function __invoke()
    {
        Gate::authorize('manageMasterData');

        return view('admin.master-data.education');
    }
}

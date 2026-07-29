<?php

namespace App\Http\Controllers\Admin\ImportExport;

use App\Http\Controllers\Controller;
use App\Livewire\Admin\ImportExport\UserComponent;

/**
 * @deprecated Digantikan oleh Livewire component admin.import-export.user
 * @see UserComponent
 */
class UsersPageController extends Controller
{
    public function __invoke()
    {
        $this->authorize('viewUserImportExport');

        return view('admin.import-export.users');
    }
}

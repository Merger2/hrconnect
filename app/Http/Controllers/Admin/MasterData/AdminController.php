<?php

namespace App\Http\Controllers\Admin\MasterData;

use App\Http\Controllers\Controller;
use App\Livewire\Admin\MasterData\Admin;

/**
 * @deprecated Digantikan oleh Livewire component admin.master-data.admin
 * @see Admin
 */
class AdminController extends Controller
{
    public function __invoke()
    {
        $this->authorize('view_admin_accounts');

        return view('admin.master-data.admin');
    }
}

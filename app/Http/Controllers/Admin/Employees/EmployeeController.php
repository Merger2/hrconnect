<?php

namespace App\Http\Controllers\Admin\Employees;

use App\Http\Controllers\Controller;
use App\Livewire\Admin\EmployeeComponent;
use App\Models\Employee;

/**
 * @deprecated Digantikan oleh Livewire component admin.employee-component
 * @see EmployeeComponent
 */
class EmployeeController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Employee::class);

        return view('admin.employees.index');
    }

    public function show(string $id)
    {
        $this->authorize('view', Employee::class);
    }
}

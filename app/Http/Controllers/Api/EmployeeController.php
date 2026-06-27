<?php

namespace App\Http\Controllers\Api;

use App\Enums\EmployeeStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ListEmployeeRequest;
use App\Http\Requests\Api\StoreEmployeeRequest;
use App\Http\Requests\Api\UpdateEmployeeRequest;
use App\Http\Resources\EmployeePiiResource;
use App\Http\Resources\EmployeeResource;
use App\Models\Employee;
use App\Models\User;
use Dedoc\Scramble\Attributes\BodyParameter;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

#[Group('Employees')]
class EmployeeController extends Controller
{
    #[Endpoint(title: 'List Employees', description: 'Paginated employee directory with search and filter. Flow: Employee Management (directory).')]
    #[QueryParameter(name: 'branch_id', description: 'Filter by branch', type: 'integer')]
    #[QueryParameter(name: 'department_id', description: 'Filter by department', type: 'integer')]
    #[QueryParameter(name: 'status', description: 'Filter by status', type: 'string')]
    #[QueryParameter(name: 'search', description: 'Search by name or employee number (min 2 chars)', type: 'string')]
    #[QueryParameter(name: 'page', description: 'Page number', type: 'integer')]
    #[QueryParameter(name: 'per_page', description: 'Items per page (max 100)', type: 'integer')]
    public function index(ListEmployeeRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Employee::class);

        $data = $request->validated();
        $perPage = (int) ($data['per_page'] ?? 20);

        $query = Employee::query()
            ->with([
                'branch:id,name',
                'department:id,name',
                'position:id,name,grade',
            ])
            ->orderBy('full_name');

        if (isset($data['branch_id'])) {
            $query->where('branch_id', (int) $data['branch_id']);
        }
        if (isset($data['department_id'])) {
            $query->where('department_id', (int) $data['department_id']);
        }
        if (isset($data['status'])) {
            $query->where('status', $data['status']);
        }
        if (isset($data['search'])) {
            $term = $data['search'];
            $query->where(function ($q) use ($term) {
                $q->where('full_name', 'like', "%{$term}%")
                    ->orWhere('employee_number', 'like', "%{$term}%");
            });
        }

        $paginated = $query->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => EmployeeResource::collection($paginated->getCollection())->resolve($request),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ]);
    }

    #[Endpoint(title: 'Get Employee', description: 'Get employee detail with user account, branch, department, position. Flow: Employee Management (detail).')]
    public function show(Request $request, Employee $employee): JsonResponse
    {
        $this->authorize('view', $employee);

        $employee->load([
            'user:id,email,email_verified_at',
            'branch:id,name',
            'department:id,name',
            'position:id,name,grade,basic_salary',
            'shift:id,name',
            'manager:id,full_name',
        ]);

        return response()->json([
            'status' => 'success',
            'data' => EmployeeResource::make($employee)->resolve($request),
        ]);
    }

    #[Endpoint(title: 'Create Employee', description: 'Create a new employee with user account and employment details. Flow: Employee Management (create).')]
    #[BodyParameter(name: 'name', description: 'User display name', required: true, type: 'string')]
    #[BodyParameter(name: 'email', description: 'User email (must be unique)', required: true, type: 'string')]
    #[BodyParameter(name: 'password', description: 'Account password (min 8 chars)', required: true, type: 'string')]
    #[BodyParameter(name: 'employee_number', description: 'Unique employee number', required: true, type: 'string')]
    #[BodyParameter(name: 'full_name', description: 'Employee full legal name', required: true, type: 'string')]
    #[BodyParameter(name: 'company_id', description: 'Company ID', required: true, type: 'integer')]
    #[BodyParameter(name: 'branch_id', description: 'Branch ID', required: true, type: 'integer')]
    #[BodyParameter(name: 'department_id', description: 'Department ID', required: true, type: 'integer')]
    #[BodyParameter(name: 'position_id', description: 'Position ID', required: true, type: 'integer')]
    #[BodyParameter(name: 'gender', description: 'Gender (L/P)', required: true, type: 'string')]
    #[BodyParameter(name: 'marital_status', description: 'Marital status', required: true, type: 'string')]
    #[BodyParameter(name: 'employment_type', description: 'Type of employment', required: true, type: 'string')]
    #[BodyParameter(name: 'birth_date', description: 'Date of birth (Y-m-d)', required: true, type: 'string', format: 'date')]
    #[BodyParameter(name: 'join_date', description: 'Join date (Y-m-d)', required: true, type: 'string', format: 'date')]
    #[BodyParameter(name: 'salary_type', description: 'Salary type (monthly/daily/hourly)', required: true, type: 'string')]
    public function store(StoreEmployeeRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'password_changed_at' => null,
        ]);

        $employee = Employee::create(array_merge(
            collect($data)->except(['name', 'email', 'password'])->toArray(),
            ['user_id' => $user->id, 'status' => EmployeeStatus::ACTIVE]
        ));

        $user->assignRole('employee');

        event(new Registered($user));

        return response()->json([
            'status' => 'success',
            'message' => 'Karyawan berhasil ditambahkan',
            'data' => EmployeeResource::make($employee->load([
                'user', 'branch', 'department', 'position', 'shift', 'manager',
            ]))->resolve($request),
        ], 201);
    }

    #[Endpoint(title: 'Update Employee', description: 'Update employee personal and employment data. Flow: Employee Management (update).')]
    #[BodyParameter(name: 'full_name', description: 'Employee full name', required: false, type: 'string')]
    #[BodyParameter(name: 'phone', description: 'Phone number (+62 format)', required: false, type: 'string')]
    #[BodyParameter(name: 'company_id', description: 'Company ID', required: false, type: 'integer')]
    #[BodyParameter(name: 'branch_id', description: 'Branch ID', required: false, type: 'integer')]
    #[BodyParameter(name: 'department_id', description: 'Department ID', required: false, type: 'integer')]
    #[BodyParameter(name: 'position_id', description: 'Position ID', required: false, type: 'integer')]
    #[BodyParameter(name: 'employment_type', description: 'Type of employment', required: false, type: 'string')]
    #[BodyParameter(name: 'status', description: 'Employment status', required: false, type: 'string')]
    public function update(UpdateEmployeeRequest $request, Employee $employee): JsonResponse
    {
        $this->authorize('update', $employee);

        $data = $request->validated();

        if (isset($data['status']) && in_array($data['status'], [
            EmployeeStatus::RESIGNED->value,
            EmployeeStatus::TERMINATED->value,
            EmployeeStatus::DECEASED->value,
        ])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Status tidak dapat diubah langsung. Gunakan endpoint terminasi untuk mengubah status resign/terminated/deceased.',
            ], 422);
        }

        $employee->update($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Data karyawan berhasil diperbarui',
            'data' => EmployeeResource::make($employee->fresh()->load([
                'user', 'branch', 'department', 'position', 'shift', 'manager',
            ]))->resolve($request),
        ]);
    }

    #[Endpoint(title: 'Delete Employee', description: 'Soft-delete an employee record. Flow: Employee Management (delete).')]
    public function destroy(Request $request, Employee $employee): JsonResponse
    {
        $this->authorize('delete', $employee);

        $employee->delete(); // soft delete

        return response()->json([
            'status' => 'success',
            'message' => 'Karyawan berhasil di-deactivate (soft delete)',
        ]);
    }

    #[Endpoint(title: 'Get Employee PII', description: 'Reveal sensitive employee PII. Requires manage_employees and records audit log. Flow: Employee Management (PII reveal).')]
    public function showPii(Request $request, Employee $employee): JsonResponse
    {
        $this->authorize('viewPii', $employee);

        activity('security')
            ->performedOn($employee)
            ->causedBy($request->user())
            ->log('Mengakses data PII sensitif karyawan tanpa masking.');

        return response()->json([
            'status' => 'success',
            'data' => EmployeePiiResource::make($employee)->resolve($request),
        ]);
    }
}

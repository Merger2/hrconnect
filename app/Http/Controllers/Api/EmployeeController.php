<?php

namespace App\Http\Controllers\Api;

use App\Enums\EmployeeStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreEmployeeRequest;
use App\Http\Requests\Api\UpdateEmployeeRequest;
use App\Models\Employee;
use App\Models\User;
use Dedoc\Scramble\Attributes\BodyParameter;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

#[Group('Employees')]
class EmployeeController extends Controller
{
    #[Endpoint(title: 'List Employees', description: 'Paginated employee directory with search and filter.')]
    #[QueryParameter(name: 'branch_id', description: 'Filter by branch', type: 'integer')]
    #[QueryParameter(name: 'department_id', description: 'Filter by department', type: 'integer')]
    #[QueryParameter(name: 'status', description: 'Filter by status', type: 'string')]
    #[QueryParameter(name: 'search', description: 'Search by name or employee number (min 2 chars)', type: 'string')]
    #[QueryParameter(name: 'page', description: 'Page number', type: 'integer')]
    #[QueryParameter(name: 'per_page', description: 'Items per page (max 100)', type: 'integer')]
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'branch_id' => ['nullable', 'integer'],
            'department_id' => ['nullable', 'integer'],
            'status' => ['nullable', 'string'],
            'search' => ['nullable', 'string', 'min:2'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $this->authorize('viewAny', Employee::class);

        $perPage = (int) $request->input('per_page', 20);

        $query = Employee::query()
            ->with([
                'branch:id,name',
                'department:id,name',
                'position:id,name,grade',
            ])
            ->orderBy('full_name');

        if ($request->filled('branch_id')) {
            $query->where('branch_id', (int) $request->input('branch_id'));
        }
        if ($request->filled('department_id')) {
            $query->where('department_id', (int) $request->input('department_id'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('search')) {
            $term = $request->input('search');
            $query->where(function ($q) use ($term) {
                $q->where('full_name', 'like', "%{$term}%")
                    ->orWhere('employee_number', 'like', "%{$term}%");
            });
        }

        $paginated = $query->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => $paginated->getCollection()->map(fn (Employee $e) => $this->formatEmployeeListing($e)),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ]);
    }

    #[Endpoint(title: 'Get Employee', description: 'Get employee detail with user account, branch, department, position.')]
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
            'data' => $this->formatEmployeeFull($employee),
        ]);
    }

    #[Endpoint(title: 'Create Employee', description: 'Create a new employee with user account and employment details.')]
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
        ]);

        $employee = Employee::create(array_merge(
            collect($data)->except(['name', 'email', 'password'])->toArray(),
            ['user_id' => $user->id, 'status' => EmployeeStatus::ACTIVE]
        ));

        $user->assignRole('employee');

        return response()->json([
            'status' => 'success',
            'message' => 'Karyawan berhasil ditambahkan',
            'data' => $this->formatEmployeeFull($employee->load([
                'user', 'branch', 'department', 'position', 'shift', 'manager',
            ])),
        ], 201);
    }

    #[Endpoint(title: 'Update Employee', description: 'Update employee personal and employment data.')]
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
        $data = $request->validated();

        $employee->update($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Data karyawan berhasil diperbarui',
            'data' => $this->formatEmployeeFull($employee->fresh()->load([
                'user', 'branch', 'department', 'position', 'shift', 'manager',
            ])),
        ]);
    }

    #[Endpoint(title: 'Delete Employee', description: 'Soft-delete an employee record.')]
    public function destroy(Request $request, Employee $employee): JsonResponse
    {
        $this->authorize('delete', $employee);

        $employee->delete(); // soft delete

        return response()->json([
            'status' => 'success',
            'message' => 'Karyawan berhasil di-deactivate (soft delete)',
        ]);
    }

    private function formatEmployeeListing(Employee $e): array
    {
        return [
            'id' => $e->id,
            'employee_number' => $e->employee_number,
            'full_name' => $e->full_name,
            'status' => $e->status?->value,
            'employment_type' => $e->employment_type?->value,
            'branch' => $e->branch?->only(['id', 'name']),
            'department' => $e->department?->only(['id', 'name']),
            'position' => $e->position?->only(['id', 'name', 'grade']),
        ];
    }

    private function formatEmployeeFull(Employee $e): array
    {
        return [
            'id' => $e->id,
            'user_id' => $e->user_id,
            'employee_number' => $e->employee_number,
            'full_name' => $e->full_name,
            'email' => $e->user?->email,
            'phone' => $e->phone,
            'nik' => $e->nik,
            'npwp' => $e->npwp,
            'bank_name' => $e->bank_name,
            'bank_account_number' => $e->bank_account_number,
            'gender' => $e->gender?->value,
            'marital_status' => $e->marital_status?->value,
            'blood_type' => $e->blood_type?->value,
            'education_level' => $e->education_level?->value,
            'birth_date' => $e->birth_date?->toDateString(),
            'join_date' => $e->join_date?->toDateString(),
            'employment_type' => $e->employment_type?->value,
            'salary_type' => $e->salary_type?->value,
            'status' => $e->status?->value,
            'address_detail' => $e->address_detail,
            'face_registered' => ! empty($e->getRawOriginal('face_embedding')),
            'pin_set' => ! empty($e->pin),
            'branch' => $e->branch?->only(['id', 'name']),
            'department' => $e->department?->only(['id', 'name']),
            'position' => $e->position?->only(['id', 'name', 'grade', 'basic_salary']),
            'shift' => $e->shift?->only(['id', 'name']),
            'manager' => $e->manager?->only(['id', 'full_name']),
            'created_at' => $e->created_at?->toIso8601String(),
        ];
    }
}

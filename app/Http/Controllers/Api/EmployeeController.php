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
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

#[Group('Employees')]
class EmployeeController extends Controller
{
    #[Endpoint(title: 'List Employees', description: 'Paginated employee directory with search and filter. Flow: Employee Management (directory).')]
    #[QueryParameter(name: 'branch_id', description: 'Filter by branch', type: 'integer')]
    #[QueryParameter(name: 'division_id', description: 'Filter by department', type: 'integer')]
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
                'division:id,name',
                'position:id,name,grade',
            ])
            ->orderBy('full_name');

        if (isset($data['branch_id'])) {
            $query->where('branch_id', (int) $data['branch_id']);
        }
        if (isset($data['division_id'])) {
            $query->where('division_id', (int) $data['division_id']);
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
            'division:id,name',
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
    #[BodyParameter(name: 'division_id', description: 'Division ID', required: true, type: 'integer')]
    #[BodyParameter(name: 'position_id', description: 'Position ID', required: true, type: 'integer')]
    #[BodyParameter(name: 'gender', description: 'Gender (L/P)', required: true, type: 'string')]
    #[BodyParameter(name: 'marital_status', description: 'Marital status', required: true, type: 'string')]
    #[BodyParameter(name: 'employment_type', description: 'Type of employment', required: true, type: 'string')]
    #[BodyParameter(name: 'birth_date', description: 'Date of birth (Y-m-d)', required: true, type: 'string', format: 'date')]
    #[BodyParameter(name: 'join_date', description: 'Join date (Y-m-d)', required: true, type: 'string', format: 'date')]
    #[BodyParameter(name: 'salary_type', description: 'Salary type (monthly/daily/hourly)', required: true, type: 'string')]
    public function store(StoreEmployeeRequest $request): JsonResponse
    {
        $this->authorize('create', Employee::class);

        $data = $request->validated();

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'group' => 'user',
        ]);

        $employee = Employee::create([
            'user_id' => $user->id,
            'employee_number' => $data['employee_number'],
            'full_name' => $data['full_name'],
            'company_id' => $data['company_id'],
            'branch_id' => $data['branch_id'],
            'division_id' => $data['division_id'],
            'position_id' => $data['position_id'],
            'gender' => $data['gender'],
            'marital_status' => $data['marital_status'],
            'employment_type' => $data['employment_type'],
            'birth_date' => $data['birth_date'],
            'join_date' => $data['join_date'],
            'salary_type' => $data['salary_type'],
            'nip' => $data['nip'] ?? null,
            'phone' => $data['phone'],
            // employees.phone & employees.nik NOT NULL tanpa default — ikuti
            // pola UserForm (web): phone required, nik di-generate fallback.
            'nik' => $data['nik'] ?? 'NIK-'.Str::random(12),
            'education_level' => $data['education_level'],
            'institution_name' => $data['institution_name'],
            'graduation_year' => $data['graduation_year'],
            'basic_salary' => $data['basic_salary'] ?? 0,
            'address_detail' => $data['address_detail'] ?? null,
            'bank_name' => $data['bank_name'] ?? null,
            'bank_account_number' => $data['bank_account_number'] ?? null,
            'bank_account_holder' => $data['bank_account_holder'] ?? null,
        ]);

        $employee->load([
            'user:id,email',
            'branch:id,name',
            'division:id,name',
            'position:id,name,grade,basic_salary',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Karyawan berhasil dibuat',
            'data' => EmployeeResource::make($employee)->resolve($request),
        ], 201);
    }

    #[Endpoint(title: 'Update Employee', description: 'Update employee personal and employment data. Flow: Employee Management (update).')]
    #[BodyParameter(name: 'full_name', description: 'Employee full name', required: false, type: 'string')]
    #[BodyParameter(name: 'phone', description: 'Phone number (+62 format)', required: false, type: 'string')]
    #[BodyParameter(name: 'company_id', description: 'Company ID', required: false, type: 'integer')]
    #[BodyParameter(name: 'branch_id', description: 'Branch ID', required: false, type: 'integer')]
    #[BodyParameter(name: 'division_id', description: 'Division ID', required: false, type: 'integer')]
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
                'user', 'branch', 'division', 'position', 'shift', 'manager',
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

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $employee = $user->employee;

        if (! $employee) {
            return response()->json(['status' => 'error', 'message' => 'Employee record not found'], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $employee->id,
                'name' => $employee->full_name ?? $user->name,
                'nip' => $employee->nip,
                'position' => $employee->position?->title,
                'division' => $employee->division?->name,
                'email' => $user->email,
            ],
        ]);
    }
}

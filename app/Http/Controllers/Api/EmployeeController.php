<?php

namespace App\Http\Controllers\Api;

use App\Enums\EmployeeStatus;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * EmployeeController — direktori karyawan untuk HR Manager + Super Admin.
 *
 * Authorization via EmployeePolicy + middleware `permission:view_employees`
 * pada route group. forceDelete super-admin only.
 */
class EmployeeController extends Controller
{
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

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Employee::class);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'employee_number' => ['required', 'string', 'unique:employees,employee_number'],
            'full_name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'regex:/^(\+62|0)\d{9,12}$/'],
            'nik' => ['nullable', 'string'],
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'department_id' => ['required', 'integer', 'exists:departments,id'],
            'position_id' => ['required', 'integer', 'exists:positions,id'],
            'parent_id' => ['nullable', 'integer', 'exists:employees,id'],
            'gender' => ['required', 'in:L,P'],
            'marital_status' => ['required', 'in:single,married,divorced,widowed'],
            'employment_type' => ['required', 'in:permanent,contract,probation,intern'],
            'birth_date' => ['required', 'date'],
            'join_date' => ['required', 'date'],
            'salary_type' => ['required', 'in:monthly,daily,hourly'],
            'blood_type' => ['nullable', 'in:A+,A-,B+,B-,AB+,AB-,O+,O-'],
            'education_level' => ['nullable', 'in:sd,smp,sma,d3,s1,s2,s3'],
        ]);

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

    public function update(Request $request, Employee $employee): JsonResponse
    {
        $this->authorize('update', $employee);

        $data = $request->validate([
            'full_name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'regex:/^(\+62|0)\d{9,12}$/'],
            'company_id' => ['sometimes', 'integer', 'exists:companies,id'],
            'branch_id' => ['sometimes', 'integer', 'exists:branches,id'],
            'department_id' => ['sometimes', 'integer', 'exists:departments,id'],
            'position_id' => ['sometimes', 'integer', 'exists:positions,id'],
            'parent_id' => ['sometimes', 'nullable', 'integer', 'exists:employees,id'],
            'employment_type' => ['sometimes', 'in:permanent,contract,probation,intern'],
            'status' => ['sometimes', 'in:active,inactive,resigned'],
            'address_detail' => ['sometimes', 'nullable', 'string', 'max:500'],
            'bank_name' => ['sometimes', 'nullable', 'string', 'max:100'],
            'bank_account_number' => ['sometimes', 'nullable', 'regex:/^\d{8,18}$/'],
        ]);

        $employee->update($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Data karyawan berhasil diperbarui',
            'data' => $this->formatEmployeeFull($employee->fresh()->load([
                'user', 'branch', 'department', 'position', 'shift', 'manager',
            ])),
        ]);
    }

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

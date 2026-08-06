<?php

namespace App\Http\Requests\Api;

use App\Enums\EducationLevel;
use App\Models\Employee;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Employee::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:8'],
            'employee_number' => ['required', 'string', 'max:50', Rule::unique('employees', 'employee_number')],
            'full_name' => ['required', 'string', 'max:255'],
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'division_id' => ['required', 'integer', 'exists:divisions,id'],
            'position_id' => ['required', 'integer', 'exists:positions,id'],
            'gender' => ['required', 'in:L,P'],
            'marital_status' => ['required', 'string', 'max:50'],
            'employment_type' => ['required', Rule::in(['permanent', 'contract', 'probation', 'intern'])],
            'birth_date' => ['required', 'date', 'before:today'],
            'join_date' => ['required', 'date'],
            'salary_type' => ['required', Rule::in(['monthly', 'daily', 'hourly'])],
            'nip' => ['nullable', 'string', 'max:50'],
            'phone' => ['required', 'string', 'max:20'],
            'nik' => ['nullable', 'string', 'max:50'],
            'education_level' => ['required', Rule::in(array_column(EducationLevel::cases(), 'value'))],
            'institution_name' => ['required', 'string', 'max:255'],
            'graduation_year' => ['required', 'integer', 'min:1970', 'max:'.now()->year],
            'basic_salary' => ['nullable', 'numeric', 'min:0'],
            'address_detail' => ['nullable', 'string', 'max:500'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'bank_account_number' => ['nullable', 'regex:/^\d{8,18}$/'],
            'bank_account_holder' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama pengguna wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah terdaftar.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal 8 karakter.',
            'employee_number.required' => 'Nomor induk karyawan wajib diisi.',
            'employee_number.unique' => 'Nomor induk karyawan sudah terdaftar.',
            'full_name.required' => 'Nama lengkap wajib diisi.',
            'company_id.required' => 'Perusahaan wajib dipilih.',
            'company_id.exists' => 'Perusahaan tidak ditemukan.',
            'branch_id.required' => 'Cabang wajib dipilih.',
            'branch_id.exists' => 'Cabang tidak ditemukan.',
            'division_id.required' => 'Divisi wajib dipilih.',
            'division_id.exists' => 'Divisi tidak ditemukan.',
            'position_id.required' => 'Posisi/Jabatan wajib dipilih.',
            'position_id.exists' => 'Posisi tidak ditemukan.',
            'gender.required' => 'Jenis kelamin wajib dipilih.',
            'gender.in' => 'Jenis kelamin harus L atau P.',
            'marital_status.required' => 'Status pernikahan wajib diisi.',
            'employment_type.required' => 'Tipe karyawan wajib dipilih.',
            'employment_type.in' => 'Tipe karyawan tidak valid.',
            'birth_date.required' => 'Tanggal lahir wajib diisi.',
            'birth_date.before' => 'Tanggal lahir harus sebelum hari ini.',
            'join_date.required' => 'Tanggal bergabung wajib diisi.',
            'salary_type.required' => 'Tipe gaji wajib dipilih.',
            'salary_type.in' => 'Tipe gaji harus monthly, daily, atau hourly.',
            'bank_account_number.regex' => 'Nomor rekening harus terdiri dari 8-18 digit angka.',
            'basic_salary.numeric' => 'Gaji pokok harus berupa angka.',
            'basic_salary.min' => 'Gaji pokok tidak boleh negatif.',
        ];
    }
}

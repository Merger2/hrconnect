<?php

// @formatter:off
// phpcs:ignoreFile
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace App\Models{
/**
 * @property int $id
 * @property int $approver_id
 * @property \Carbon\CarbonImmutable|null $approved_at
 * @property string $approvable_type
 * @property int $approvable_id
 * @property \App\Enums\ApprovalLevel $level
 * @property \App\Enums\ApprovalStatus $status
 * @property string|null $notes
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent $approvable
 * @property-read \App\Models\Employee|null $approver
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Approval newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Approval newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Approval query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Approval whereApprovableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Approval whereApprovableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Approval whereApprovedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Approval whereApproverId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Approval whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Approval whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Approval whereLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Approval whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Approval whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Approval whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperApproval {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int|null $company_id
 * @property string $name
 * @property string $serial_number
 * @property string|null $code
 * @property string|null $category
 * @property bool $is_available
 * @property \App\Enums\AssetStatus $status
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @property-read \App\Models\Company|null $company
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\AssetHandover> $handovers
 * @property-read int|null $handovers_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset whereCategory($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset whereIsAvailable($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset whereSerialNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset withoutTrashed()
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperAsset {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $asset_id
 * @property int $employee_id
 * @property \Carbon\CarbonImmutable $handover_date
 * @property \Carbon\CarbonImmutable|null $return_date
 * @property string $condition
 * @property \App\Enums\HandoverCategory $category
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Models\Asset|null $asset
 * @property-read \App\Models\Employee|null $employee
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetHandover newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetHandover newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetHandover query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetHandover whereAssetId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetHandover whereCategory($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetHandover whereCondition($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetHandover whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetHandover whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetHandover whereHandoverDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetHandover whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetHandover whereReturnDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetHandover whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperAssetHandover {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $employee_id
 * @property int|null $shift_id
 * @property \Carbon\CarbonImmutable $date
 * @property \Carbon\CarbonImmutable|null $clock_in Null jika status=absent dari DetectAlphaAttendanceCommand
 * @property \Carbon\CarbonImmutable|null $clock_out
 * @property numeric|null $lat_in
 * @property numeric|null $long_in
 * @property bool $clock_in_is_mocked
 * @property numeric|null $clock_in_accuracy
 * @property numeric|null $lat_out
 * @property numeric|null $long_out
 * @property bool|null $clock_out_is_mocked
 * @property numeric|null $clock_out_accuracy
 * @property string|null $device_fingerprint
 * @property numeric|null $face_similarity_score Akurasi kemiripan wajah dalam persentase (%) - clock-in
 * @property \App\Enums\VerificationMethod|null $clock_out_verification_method face_verified|pin_verified|manual
 * @property numeric|null $clock_out_face_similarity_score Akurasi kemiripan wajah clock-out
 * @property string|null $photo_selfie_in
 * @property string|null $photo_selfie_out
 * @property \App\Enums\AttendanceStatus $status
 * @property bool $is_wfa
 * @property \App\Enums\WfaStatus|null $status_wfa
 * @property string|null $exception_type
 * @property string|null $exception_notes
 * @property int|null $approved_late_by
 * @property \App\Enums\VerificationMethod|null $verification_method face_verified|pin_verified|manual
 * @property string|null $wfa_note
 * @property int $late_minutes
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Approval> $approvals
 * @property-read int|null $approvals_count
 * @property-read \App\Models\Employee|null $approvedLateBy
 * @property-read \App\Models\Employee|null $employee
 * @property-read \App\Models\Overtime|null $overtime
 * @property-read \App\Models\Shift|null $shift
 * @method static \Database\Factories\AttendanceFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attendance newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attendance newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attendance onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attendance query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attendance whereApprovedLateBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attendance whereClockIn($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attendance whereClockInAccuracy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attendance whereClockInIsMocked($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attendance whereClockOut($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attendance whereClockOutAccuracy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attendance whereClockOutFaceSimilarityScore($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attendance whereClockOutIsMocked($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attendance whereClockOutVerificationMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attendance whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attendance whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attendance whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attendance whereDeviceFingerprint($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attendance whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attendance whereExceptionNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attendance whereExceptionType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attendance whereFaceSimilarityScore($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attendance whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attendance whereIsWfa($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attendance whereLatIn($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attendance whereLatOut($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attendance whereLateMinutes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attendance whereLongIn($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attendance whereLongOut($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attendance wherePhotoSelfieIn($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attendance wherePhotoSelfieOut($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attendance whereShiftId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attendance whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attendance whereStatusWfa($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attendance whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attendance whereVerificationMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attendance whereWfaNote($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attendance withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attendance withoutTrashed()
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperAttendance {}
}

namespace App\Models{
/**
 * @property int $id
 * @property \App\Enums\BpjsType $name
 * @property numeric $employer_rate
 * @property numeric $employee_rate
 * @property numeric|null $ceiling
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BpjsConfig newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BpjsConfig newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BpjsConfig query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BpjsConfig whereCeiling($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BpjsConfig whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BpjsConfig whereEmployeeRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BpjsConfig whereEmployerRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BpjsConfig whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BpjsConfig whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BpjsConfig whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperBpjsConfig {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $company_id
 * @property string $name
 * @property string $address
 * @property bool $is_main
 * @property bool $is_active
 * @property numeric|null $latitude Titik Y Pusat Kantor
 * @property numeric|null $longitude Titik X Pusat Kantor
 * @property int $radius Batas toleransi absen dalam meter
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Models\Company $company
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Department> $departments
 * @property-read int|null $departments_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Employee> $employees
 * @property-read int|null $employees_count
 * @method static \Database\Factories\BranchFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Branch newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Branch newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Branch query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Branch whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Branch whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Branch whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Branch whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Branch whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Branch whereIsMain($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Branch whereLatitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Branch whereLongitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Branch whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Branch whereRadius($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Branch whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperBranch {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string $phone
 * @property string $email
 * @property string|null $website
 * @property string $npwp
 * @property string $code
 * @property string|null $logo
 * @property bool $is_active
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Branch> $branches
 * @property-read int|null $branches_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Employee> $employees
 * @property-read int|null $employees_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\CompanySetting> $settings
 * @property-read int|null $settings_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $users
 * @property-read int|null $users_count
 * @method static \Database\Factories\CompanyFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company orWhereBlind(string $column, string $indexName, array|string $value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereBlind(string $column, string $indexName, array|string $value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereLogo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereNpwp($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereWebsite($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperCompany {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int|null $company_id
 * @property string $key
 * @property array<array-key, mixed>|null $value
 * @property string|null $description
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Models\Company|null $company
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanySetting newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanySetting newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanySetting query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanySetting whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanySetting whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanySetting whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanySetting whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanySetting whereKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanySetting whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanySetting whereValue($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperCompanySetting {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $branch_id
 * @property string $name
 * @property string $code
 * @property string|null $description
 * @property bool $is_active
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @property-read \App\Models\Branch $branch
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Employee> $employees
 * @property-read int|null $employees_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Position> $positions
 * @property-read int|null $positions_count
 * @method static \Database\Factories\DepartmentFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department whereBranchId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department withoutTrashed()
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperDepartment {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $employee_id
 * @property string $device_uuid
 * @property string|null $device_type
 * @property string|null $device_name
 * @property string|null $browser
 * @property string|null $os
 * @property bool $is_verified
 * @property \Carbon\CarbonImmutable|null $verified_at
 * @property \Carbon\CarbonImmutable|null $last_used_at
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Models\Employee|null $employee
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Device newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Device newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Device query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Device whereBrowser($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Device whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Device whereDeviceName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Device whereDeviceType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Device whereDeviceUuid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Device whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Device whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Device whereIsVerified($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Device whereLastUsedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Device whereOs($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Device whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Device whereVerifiedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperDevice {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $user_id
 * @property int|null $parent_id
 * @property int $company_id
 * @property int $branch_id
 * @property int $department_id
 * @property int $position_id
 * @property int|null $shift_id
 * @property int|null $province_id
 * @property int|null $city_id
 * @property int|null $district_id
 * @property int|null $village_id
 * @property string|null $postal_code
 * @property string|null $address_detail
 * @property string $employee_number
 * @property string $full_name
 * @property string $phone
 * @property string|null $bank_account_number
 * @property string|null $bank_name
 * @property string|null $npwp
 * @property string $nik
 * @property \App\Enums\MaritalStatus $marital_status
 * @property \App\Enums\BloodType|null $blood_type
 * @property \App\Enums\Gender $gender
 * @property \App\Enums\EmployeeStatus $status
 * @property \Carbon\CarbonImmutable $birth_date
 * @property \Carbon\CarbonImmutable $join_date
 * @property \App\Enums\EmploymentType $employment_type
 * @property \Carbon\CarbonImmutable|null $contract_start_date
 * @property \Carbon\CarbonImmutable|null $contract_end_date
 * @property \Carbon\CarbonImmutable|null $resign_date
 * @property \Carbon\CarbonImmutable|null $deceased_date
 * @property \App\Enums\TerminationType|null $termination_type
 * @property string|null $termination_reason
 * @property string|null $phk_variant
 * @property string|null $pin bcrypt hash, 6 digit PIN absensi
 * @property string|null $photo
 * @property mixed|null $face_embedding Menyimpan vektor embedding wajah untuk keperluan absensi berbasis wajah
 * @property \App\Enums\EducationLevel $education_level
 * @property string $institution_name
 * @property string|null $major
 * @property int $graduation_year
 * @property \App\Enums\SalaryType $salary_type
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Approval> $approvals
 * @property-read int|null $approvals_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Attendance> $attendances
 * @property-read int|null $attendances_count
 * @property-read \App\Models\Branch $branch
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\FamilyDetail> $children
 * @property-read int|null $children_count
 * @property-read \Laravolt\Indonesia\Models\City|null $city
 * @property-read \App\Models\Company $company
 * @property-read \App\Models\User|null $creator
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\LeaveBalance> $currentYearLeaveBalances
 * @property-read int|null $current_year_leave_balances_count
 * @property-read \App\Models\Department|null $department
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Device> $devices
 * @property-read int|null $devices_count
 * @property-read \Laravolt\Indonesia\Models\District|null $district
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\FamilyDetail> $families
 * @property-read int|null $families_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\AssetHandover> $handovers
 * @property-read int|null $handovers_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\LeaveBalance> $leaveBalances
 * @property-read int|null $leave_balances_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Leave> $leaves
 * @property-read int|null $leaves_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Loan> $loans
 * @property-read int|null $loans_count
 * @property-read Employee|null $manager
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Overtime> $overtimes
 * @property-read int|null $overtimes_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Payroll> $payrolls
 * @property-read int|null $payrolls_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PerformanceReview> $performanceReviews
 * @property-read int|null $performance_reviews_count
 * @property-read \App\Models\Position|null $position
 * @property-read \Laravolt\Indonesia\Models\Province|null $province
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Reimbursement> $reimbursements
 * @property-read int|null $reimbursements_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PerformanceReview> $reviewedVersions
 * @property-read int|null $reviewed_versions_count
 * @property-read \App\Models\Shift|null $shift
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ShiftSchedule> $shiftSchedules
 * @property-read int|null $shift_schedules_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Employee> $subordinates
 * @property-read int|null $subordinates_count
 * @property-read \App\Models\User|null $updater
 * @property-read \App\Models\User|null $user
 * @property-read \Laravolt\Indonesia\Models\Village|null $village
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee active()
 * @method static \Database\Factories\EmployeeFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee orWhereBlind(string $column, string $indexName, array|string $value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereAddressDetail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereBankAccountNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereBankName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereBirthDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereBlind(string $column, string $indexName, array|string $value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereBloodType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereBranchId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereCityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereContractEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereContractStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereDeceasedDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereDepartmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereDistrictId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereEducationLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereEmployeeNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereEmploymentType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereFaceEmbedding($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereFullName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereGender($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereGraduationYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereInstitutionName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereJoinDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereMajor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereMaritalStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereNik($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereNpwp($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereParentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee wherePhkVariant($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee wherePhoto($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee wherePin($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee wherePositionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee wherePostalCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereProvinceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereResignDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereSalaryType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereShiftId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereTerminationReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereTerminationType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereUpdatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereVillageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee withoutTrashed()
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperEmployee {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $employee_id
 * @property string $name
 * @property \App\Enums\FamilyRelationship $relationship
 * @property \App\Enums\Gender|null $gender
 * @property string|null $nik
 * @property \Carbon\CarbonImmutable|null $birth_date
 * @property string|null $job
 * @property string|null $phone
 * @property string|null $address
 * @property bool $is_emergency
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Models\Employee|null $employee
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FamilyDetail newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FamilyDetail newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FamilyDetail orWhereBlind(string $column, string $indexName, array|string $value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FamilyDetail query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FamilyDetail whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FamilyDetail whereBirthDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FamilyDetail whereBlind(string $column, string $indexName, array|string $value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FamilyDetail whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FamilyDetail whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FamilyDetail whereGender($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FamilyDetail whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FamilyDetail whereIsEmergency($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FamilyDetail whereJob($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FamilyDetail whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FamilyDetail whereNik($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FamilyDetail wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FamilyDetail whereRelationship($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FamilyDetail whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperFamilyDetail {}
}

namespace App\Models{
/**
 * @property int $id
 * @property \Carbon\CarbonImmutable $date
 * @property string $name
 * @property bool $is_active
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @method static \Database\Factories\HolidayFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Holiday newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Holiday newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Holiday query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Holiday whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Holiday whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Holiday whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Holiday whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Holiday whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Holiday whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperHoliday {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $knowledgeable_type
 * @property int $knowledgeable_id
 * @property string $title
 * @property string $content
 * @property \App\Enums\KnowledgeBaseCategory $category
 * @property mixed|null $embedding
 * @property \App\Enums\KnowledgeBaseStatus $status
 * @property string|null $source_document
 * @property int|null $page_number
 * @property array<array-key, mixed>|null $metadata
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent $knowledgeable
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeBase newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeBase newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeBase query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeBase whereCategory($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeBase whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeBase whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeBase whereEmbedding($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeBase whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeBase whereKnowledgeableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeBase whereKnowledgeableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeBase whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeBase wherePageNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeBase whereSourceDocument($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeBase whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeBase whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeBase whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperKnowledgeBase {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $employee_id
 * @property int $leave_type_id
 * @property \Carbon\CarbonImmutable $start_date
 * @property \Carbon\CarbonImmutable $end_date
 * @property \App\Enums\DayType $day_type
 * @property numeric $total_days
 * @property string $reason
 * @property string|null $proof_file
 * @property string|null $rejection_reason
 * @property \App\Enums\RequestStatus $status
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Approval> $approvals
 * @property-read int|null $approvals_count
 * @property-read \App\Models\Employee|null $employee
 * @property-read \App\Models\LeaveType $leaveType
 * @method static \Database\Factories\LeaveFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Leave newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Leave newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Leave onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Leave query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Leave whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Leave whereDayType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Leave whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Leave whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Leave whereEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Leave whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Leave whereLeaveTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Leave whereProofFile($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Leave whereReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Leave whereRejectionReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Leave whereStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Leave whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Leave whereTotalDays($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Leave whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Leave withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Leave withoutTrashed()
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperLeave {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $employee_id
 * @property int $leave_type_id
 * @property int $year
 * @property numeric $quota
 * @property numeric $used
 * @property numeric $carry_forward
 * @property \Carbon\CarbonImmutable|null $carry_forward_deadline
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Models\Employee|null $employee
 * @property-read \App\Models\LeaveType $leaveType
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveBalance newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveBalance newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveBalance query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveBalance whereCarryForward($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveBalance whereCarryForwardDeadline($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveBalance whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveBalance whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveBalance whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveBalance whereLeaveTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveBalance whereQuota($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveBalance whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveBalance whereUsed($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveBalance whereYear($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperLeaveBalance {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string $code
 * @property int $quota
 * @property bool $is_paid
 * @property bool $deducts_from_quota
 * @property bool $is_active
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\LeaveBalance> $leaveBalances
 * @property-read int|null $leave_balances_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Leave> $leaves
 * @property-read int|null $leaves_count
 * @method static \Database\Factories\LeaveTypeFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveType newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveType newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveType query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveType whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveType whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveType whereDeductsFromQuota($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveType whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveType whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveType whereIsPaid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveType whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveType whereQuota($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveType whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperLeaveType {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $employee_id
 * @property string|null $rejection_reason
 * @property int|null $created_by
 * @property numeric $amount
 * @property numeric $interest_rate
 * @property int $tenor_months
 * @property numeric $monthly_installment
 * @property \App\Enums\LoanStatus $status
 * @property bool $is_settled
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Models\User|null $creator
 * @property-read \App\Models\Employee|null $employee
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\LoanInstallment> $installments
 * @property-read int|null $installments_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Loan newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Loan newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Loan onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Loan query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Loan whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Loan whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Loan whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Loan whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Loan whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Loan whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Loan whereInterestRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Loan whereIsSettled($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Loan whereMonthlyInstallment($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Loan whereRejectionReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Loan whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Loan whereTenorMonths($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Loan whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Loan withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Loan withoutTrashed()
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperLoan {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $loan_id
 * @property int|null $payroll_id
 * @property numeric $amount_paid
 * @property int $installment_number
 * @property \App\Enums\LoanInstallmentStatus $status
 * @property \Carbon\CarbonImmutable $due_date
 * @property \Carbon\CarbonImmutable|null $paid_at
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Models\Loan|null $loan
 * @property-read \App\Models\Payroll|null $payroll
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanInstallment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanInstallment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanInstallment query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanInstallment whereAmountPaid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanInstallment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanInstallment whereDueDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanInstallment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanInstallment whereInstallmentNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanInstallment whereLoanId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanInstallment wherePaidAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanInstallment wherePayrollId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanInstallment whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanInstallment whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperLoanInstallment {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $employee_id
 * @property int|null $attendance_id
 * @property \Carbon\CarbonImmutable $date
 * @property \Carbon\CarbonImmutable|null $start_time
 * @property \Carbon\CarbonImmutable|null $end_time
 * @property string|null $description
 * @property numeric|null $total_hours
 * @property numeric|null $amount
 * @property string|null $rejection_reason
 * @property \App\Enums\RequestStatus $status
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Approval> $approvals
 * @property-read int|null $approvals_count
 * @property-read \App\Models\Attendance|null $attendance
 * @property-read \App\Models\Employee|null $employee
 * @method static \Database\Factories\OvertimeFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Overtime newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Overtime newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Overtime onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Overtime query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Overtime whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Overtime whereAttendanceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Overtime whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Overtime whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Overtime whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Overtime whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Overtime whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Overtime whereEndTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Overtime whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Overtime whereRejectionReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Overtime whereStartTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Overtime whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Overtime whereTotalHours($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Overtime whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Overtime withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Overtime withoutTrashed()
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperOvertime {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $employee_id
 * @property string $period
 * @property numeric $basic_salary
 * @property numeric $total_allowance
 * @property numeric $gross_salary
 * @property numeric $overtime_pay
 * @property numeric $pph21
 * @property numeric $bpjs_health
 * @property numeric $bpjs_employment
 * @property numeric $loan_deduction
 * @property numeric $attendance_penalty
 * @property numeric $total_deduction
 * @property numeric $net_salary
 * @property \App\Enums\PayrollStatus $status
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PayrollAdjustment> $adjustments
 * @property-read int|null $adjustments_count
 * @property-read \App\Models\Employee|null $employee
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PayrollItem> $items
 * @property-read int|null $items_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\LoanInstallment> $loanInstallments
 * @property-read int|null $loan_installments_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Reimbursement> $reimbursements
 * @property-read int|null $reimbursements_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payroll newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payroll newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payroll onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payroll query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payroll whereAttendancePenalty($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payroll whereBasicSalary($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payroll whereBpjsEmployment($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payroll whereBpjsHealth($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payroll whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payroll whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payroll whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payroll whereGrossSalary($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payroll whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payroll whereLoanDeduction($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payroll whereNetSalary($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payroll whereOvertimePay($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payroll wherePeriod($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payroll wherePph21($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payroll whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payroll whereTotalAllowance($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payroll whereTotalDeduction($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payroll whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payroll withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payroll withoutTrashed()
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperPayroll {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $payroll_id
 * @property numeric $amount
 * @property string $reason
 * @property int|null $created_by
 * @property \Carbon\CarbonImmutable $applied_to_period
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Models\User|null $creator
 * @property-read \App\Models\Payroll|null $payroll
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollAdjustment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollAdjustment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollAdjustment query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollAdjustment whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollAdjustment whereAppliedToPeriod($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollAdjustment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollAdjustment whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollAdjustment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollAdjustment wherePayrollId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollAdjustment whereReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollAdjustment whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperPayrollAdjustment {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $payroll_id
 * @property string $name
 * @property numeric $amount
 * @property \App\Enums\PayrollItemType $type
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Models\Payroll|null $payroll
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollItem query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollItem whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollItem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollItem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollItem whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollItem wherePayrollId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollItem whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollItem whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperPayrollItem {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $employee_id
 * @property int $reviewer_id
 * @property string $status
 * @property \Carbon\CarbonImmutable|null $review_date
 * @property string|null $period
 * @property numeric|null $final_score
 * @property string|null $notes
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @property-read \App\Models\Employee|null $employee
 * @property-read \App\Models\Employee|null $reviewer
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PerformanceReview newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PerformanceReview newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PerformanceReview onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PerformanceReview query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PerformanceReview whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PerformanceReview whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PerformanceReview whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PerformanceReview whereFinalScore($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PerformanceReview whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PerformanceReview whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PerformanceReview wherePeriod($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PerformanceReview whereReviewDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PerformanceReview whereReviewerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PerformanceReview whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PerformanceReview whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PerformanceReview withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PerformanceReview withoutTrashed()
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperPerformanceReview {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $department_id
 * @property string $name
 * @property string $code
 * @property int $grade
 * @property numeric $basic_salary
 * @property numeric $allowance_jabatan
 * @property bool $is_active
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @property-read \App\Models\Department|null $department
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Employee> $employees
 * @property-read int|null $employees_count
 * @method static \Database\Factories\PositionFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Position newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Position newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Position onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Position query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Position whereAllowanceJabatan($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Position whereBasicSalary($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Position whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Position whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Position whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Position whereDepartmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Position whereGrade($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Position whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Position whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Position whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Position whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Position withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Position withoutTrashed()
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperPosition {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $employee_id
 * @property int|null $payroll_id
 * @property int|null $category_id
 * @property string $title
 * @property \Carbon\CarbonImmutable $expense_date
 * @property numeric $amount
 * @property string|null $description
 * @property string|null $receipt_file
 * @property string|null $attachment_path
 * @property string|null $rejection_reason
 * @property \App\Enums\ReimbursementStatus $status
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Approval> $approvals
 * @property-read int|null $approvals_count
 * @property-read \App\Models\ReimbursementCategory|null $category
 * @property-read \App\Models\Employee|null $employee
 * @property-read \App\Models\Payroll|null $payroll
 * @method static \Database\Factories\ReimbursementFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reimbursement newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reimbursement newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reimbursement onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reimbursement query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reimbursement whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reimbursement whereAttachmentPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reimbursement whereCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reimbursement whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reimbursement whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reimbursement whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reimbursement whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reimbursement whereExpenseDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reimbursement whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reimbursement wherePayrollId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reimbursement whereReceiptFile($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reimbursement whereRejectionReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reimbursement whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reimbursement whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reimbursement whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reimbursement withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reimbursement withoutTrashed()
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperReimbursement {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int|null $company_id
 * @property string $name
 * @property string $code
 * @property bool $is_active
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Models\Company|null $company
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Reimbursement> $reimbursements
 * @property-read int|null $reimbursements_count
 * @method static \Database\Factories\ReimbursementCategoryFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReimbursementCategory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReimbursementCategory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReimbursementCategory query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReimbursementCategory whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReimbursementCategory whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReimbursementCategory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReimbursementCategory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReimbursementCategory whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReimbursementCategory whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReimbursementCategory whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperReimbursementCategory {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string $start_time
 * @property string $end_time
 * @property int $late_tolerance_minutes
 * @property bool $is_active
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Attendance> $attendances
 * @property-read int|null $attendances_count
 * @property-read mixed $duration
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ShiftSchedule> $shiftSchedules
 * @property-read int|null $shift_schedules_count
 * @method static \Database\Factories\ShiftFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereEndTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereLateToleranceMinutes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereStartTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift withoutTrashed()
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperShift {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $employee_id
 * @property int $shift_id
 * @property \Carbon\CarbonImmutable $date
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Models\Employee|null $employee
 * @property-read \App\Models\Shift|null $shift
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftSchedule newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftSchedule newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftSchedule query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftSchedule whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftSchedule whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftSchedule whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftSchedule whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftSchedule whereShiftId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftSchedule whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperShiftSchedule {}
}

namespace App\Models{
/**
 * @property int $id
 * @property \App\Enums\TerCategory $ter_category
 * @property numeric $min_income
 * @property numeric $max_income
 * @property numeric $rate
 * @property numeric|null $effective_rate
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaxConfig newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaxConfig newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaxConfig query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaxConfig whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaxConfig whereEffectiveRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaxConfig whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaxConfig whereMaxIncome($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaxConfig whereMinIncome($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaxConfig whereRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaxConfig whereTerCategory($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaxConfig whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperTaxConfig {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property \Carbon\CarbonImmutable|null $email_verified_at
 * @property string $password
 * @property string|null $google_id
 * @property \Carbon\CarbonImmutable|null $password_changed_at
 * @property string|null $remember_token
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property string|null $two_factor_confirmed_at
 * @property-read \App\Models\Company|null $company
 * @property-read \App\Models\Employee|null $employee
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Permission\Models\Permission> $permissions
 * @property-read int|null $permissions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Permission\Models\Role> $roles
 * @property-read int|null $roles_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Laravel\Sanctum\PersonalAccessToken> $tokens
 * @property-read int|null $tokens_count
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User permission($permissions, bool $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User role($roles, ?string $guard = null, bool $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereGoogleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePasswordChangedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereTwoFactorConfirmedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereTwoFactorRecoveryCodes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereTwoFactorSecret($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withoutPermission($permissions)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withoutRole($roles, ?string $guard = null)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withoutTrashed()
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperUser {}
}


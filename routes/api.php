<?php

use App\Http\Controllers\Api\ApprovalController;
use App\Http\Controllers\Api\AssetController;
use App\Http\Controllers\Api\AuthenticatedUserController;
use App\Http\Controllers\Api\BranchController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\Device\LocationController;
use App\Http\Controllers\Api\Device\OfflineAttendanceSyncController;
use App\Http\Controllers\Api\Device\PermissionsStatusController;
use App\Http\Controllers\Api\Device\PhotoUploadController;
use App\Http\Controllers\Api\DivisionController;
use App\Http\Controllers\Api\EmailVerificationController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\EmployeeTerminationController;
use App\Http\Controllers\Api\FaceController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\Integrations\AttendanceEventController;
use App\Http\Controllers\Api\KnowledgeBaseController;
use App\Http\Controllers\Api\LeaveController;
use App\Http\Controllers\Api\LoanController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\OvertimeController;
use App\Http\Controllers\Api\PayrollController;
use App\Http\Controllers\Api\PositionController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\ReimbursementController;
use App\Http\Controllers\Api\WilayahController;
use App\Http\Middleware\EnsureEmployeeDeviceApiAccount;
use App\Models\Payroll;
use App\Support\ApiTokenPermission;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — PWA / Mobile / Integration
|--------------------------------------------------------------------------
|
| - `auth:sanctum`  : token-based mobile/PWA
| - `throttle:api`  : rate limiting untuk public/protected endpoints
| - Grup bawah ini disusun by domain, bukan by controller.
|
*/

// ─── Sanctum user ────────────────────────────────────────────────
Route::middleware(['auth:sanctum', 'throttle:api'])
    ->get('/user', AuthenticatedUserController::class)
    ->name('api.user');

// ─── Public / lightly protected ──────────────────────────────────
Route::middleware('throttle:api')->group(function () {
    // Health / monitoring
    Route::get('/health', HealthController::class);

    // Email verification (token-gated inside controller, not sanctum)
    Route::controller(EmailVerificationController::class)->prefix('auth')->group(function () {
        Route::post('/email/verification-notification', 'resend');
        Route::get('/email/verify/{id}/{hash}', 'verify')->name('api.verification.verify');
    });
});

// ─── Authenticated user / employee ──────────────────────────────
Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    // Self profile
    Route::controller(ProfileController::class)->prefix('profile')->group(function () {
        Route::get('/', 'show');
        Route::put('/', 'update');
        Route::post('/password', 'changePassword');
    });

    // Face recognition (register / verify untuk login)
    Route::controller(FaceController::class)->prefix('face')->group(function () {
        Route::post('/register', 'register');
        Route::post('/verify', 'verify');
    });

    // Employee directory — HR/Admin only untuk read/write
    Route::controller(EmployeeController::class)->prefix('employees')->group(function () {
        Route::get('/', 'index')->can('view_employees');
        Route::get('/me', 'me');
        Route::post('/', 'store')->can('manage_employees');
        Route::get('/{employee}', 'show')->can('view_employees');
        Route::put('/{employee}', 'update')->can('manage_employees');
        Route::delete('/{employee}', 'destroy')->can('manage_employees');
    });

    // Notifications (user punya sendiri)
    Route::controller(NotificationController::class)->prefix('notifications')->group(function () {
        Route::get('/', 'index');
        Route::put('/read-all', 'markAllAsRead');
        Route::delete('/{notification}', 'destroy');
    });

    // Knowledge Base / chat RAG
    Route::controller(KnowledgeBaseController::class)->prefix('knowledge-base')->group(function () {
        Route::get('/', 'index')->can('view_knowledgebase');
        Route::get('/{knowledgeBase}', 'show')->can('view_knowledgebase');
        Route::post('/chat', 'chat')->can('view_knowledgebase');
        Route::post('/upload', 'upload')->can('manage_knowledgebase');
        Route::delete('/{knowledgeBase}', 'destroy')->can('manage_knowledgebase');
    });

    // User self-service — user manage own data
    Route::controller(LeaveController::class)->prefix('leaves')->group(function () {
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::get('/{leave}', 'show');
        Route::put('/{leave}', 'update');
        Route::delete('/{leave}', 'destroy');
    });

    Route::controller(ReimbursementController::class)->prefix('reimbursements')->group(function () {
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::get('/{reimbursement}', 'show');
        Route::put('/{reimbursement}', 'update');
        Route::delete('/{reimbursement}', 'destroy');
    });

    Route::controller(OvertimeController::class)->prefix('overtimes')->group(function () {
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::get('/{overtime}', 'show');
        Route::put('/{overtime}', 'update');
        Route::delete('/{overtime}', 'destroy');
    });

    Route::controller(LoanController::class)->prefix('loans')->group(function () {
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::get('/{loan}', 'show');
        Route::put('/{loan}', 'update');
        Route::post('/{loan}/installments', 'payInstallment');
        Route::delete('/{loan}', 'destroy');
    });

    // Approval queue (user sebagai approver)
    Route::controller(ApprovalController::class)->prefix('approvals')->group(function () {
        Route::get('/', 'index');
        Route::get('/history', 'history');
        Route::put('/{approval}/approve', 'approve');
        Route::put('/{approval}/reject', 'reject');
    });

    // Company data (read-only)
    Route::controller(CompanyController::class)->prefix('company')->group(function () {
        Route::get('/hours', 'getCompanyOperationalHours')->can('view_companies');
        Route::get('/branches', 'getCompanyBranches')->can('view_companies');
    });
});

// ─── Master data (authenticated, read-heavy) ────────────────────
Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    Route::controller(BranchController::class)->prefix('branches')->group(function () {
        Route::get('/', 'index')->can('view_branches');
        Route::get('/{branch}', 'show')->can('view_branches');
    });

    Route::controller(DivisionController::class)->prefix('divisions')->group(function () {
        Route::get('/', 'index')->can('view_divisions');
        Route::get('/{division}', 'show')->can('view_divisions');
    });

    Route::controller(PositionController::class)->prefix('positions')->group(function () {
        Route::get('/', 'index')->can('view_positions');
        Route::get('/{position}', 'show')->can('view_positions');
    });
});

// ─── HR / Admin-only actions ────────────────────────────────────
Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    Route::controller(EmployeeTerminationController::class)->prefix('employee-terminations')->group(function () {
        Route::get('/', 'index')->can('manage_employees');
        Route::post('/{employee}', 'store')->can('manage_employees');
        Route::post('/process-contract-end', 'processContractEnd')->can('manage_employees');
    });

    Route::controller(AssetController::class)->prefix('assets')->group(function () {
        Route::get('/', 'index')->can('view_assets');
        Route::post('/', 'store')->can('manage_assets');
        Route::get('/{asset}', 'show')->can('view_assets');
        Route::put('/{asset}', 'update')->can('manage_assets');
        Route::delete('/{asset}', 'destroy')->can('manage_assets');
    });

    Route::controller(PayrollController::class)->prefix('payrolls')->group(function () {
        Route::get('/', 'index')->can('viewAny', Payroll::class);
        Route::post('/generate', 'generate')->can('process_payroll');
        Route::get('/{payroll}', 'show')->can('view', 'payroll');
        Route::get('/{payroll}/payslip', 'payslip')->can('view_payslip');
        Route::post('/{payroll}/export-monthly', 'exportMonthly')->can('process_payroll');
        Route::post('/{payroll}/export-1721a1', 'export1721A1')->can('process_payroll');
        Route::post('/{payroll}/export-bpjs', 'exportBpjs')->can('process_payroll');
    });
});

// ─── Wilayah Data ───────────────────────────────────────────────
Route::prefix('wilayah')->middleware('throttle:wilayah')->group(function () {
    Route::get('/provinces', [WilayahController::class, 'provinces']);
    Route::get('/regencies/{provinceCode}', [WilayahController::class, 'regencies'])
        ->where('provinceCode', '[0-9]{2}');
    Route::get('/districts/{regencyCode}', [WilayahController::class, 'districts'])
        ->where('regencyCode', '[0-9]{2}\.[0-9]{2}');
    Route::get('/villages/{districtCode}', [WilayahController::class, 'villages'])
        ->where('districtCode', '[0-9]{2}\.[0-9]{2}\.[0-9]{2}');
});

// ─── Capacitor Device API ───────────────────────────────────────
Route::middleware(['auth:sanctum', EnsureEmployeeDeviceApiAccount::class, 'throttle:api'])->prefix('device')->group(function () {
    Route::post('/location', LocationController::class)->middleware('abilities:'.ApiTokenPermission::DEVICE_LOCATION);
    Route::post('/offline-attendance', OfflineAttendanceSyncController::class)->middleware('abilities:'.ApiTokenPermission::DEVICE_OFFLINE_ATTENDANCE);
    Route::post('/photo', PhotoUploadController::class)->middleware('abilities:'.ApiTokenPermission::DEVICE_PHOTO);
    Route::get('/permissions', PermissionsStatusController::class)->middleware('abilities:'.ApiTokenPermission::DEVICE_PERMISSIONS);
});

// ─── Integration Webhook ───────────────────────────────────────
Route::prefix('integrations')
    ->middleware(['throttle:attendance-integrations', 'attendance.integration.signature'])
    ->group(function () {
        Route::post('/attendance-events', [AttendanceEventController::class, 'store']);
    });

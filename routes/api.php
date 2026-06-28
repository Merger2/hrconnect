<?php

use App\Http\Controllers\Api\ApprovalController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\EmployeeTerminationController;
use App\Http\Controllers\Api\FaceController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\KnowledgeBaseController;
use App\Http\Controllers\Api\LeaveController;
use App\Http\Controllers\Api\OvertimeController;
use App\Http\Controllers\Api\PayrollController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\ReimbursementController;
use App\Models\KnowledgeBase;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes (v1)
|--------------------------------------------------------------------------
|
| Prefix: /api/v1 (defined di bootstrap/app.php apiPrefix).
| Auth: Sanctum Bearer token (PWA mobile) atau cookie stateful (SPA same-origin).
| Token expiration: null (never expire — PWA reuse sampai logout/revoke).
|
| Lihat docs/api/api-contracts.md v2.0 untuk spesifikasi penuh 47 endpoint.
|
| HTTP code policy:
| - 200 success, 201 created, 204 deleted
| - 401 token invalid/missing
| - 403 policy reject (IDOR fix)
| - 409 state conflict (already clocked in, payroll locked, dll)
| - 422 validation / business rule violation
| - 429 rate limited
*/

// ─── PUBLIC (no auth) ─────────────────────────────────────────────────

Route::get('/health', HealthController::class)->name('api.health');

Route::prefix('auth')->name('api.auth.')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:5,1') // 5 attempts per 1 menit
        ->name('login');

    Route::post('/2fa/challenge', [AuthController::class, 'twoFactorChallenge'])
        ->middleware('throttle:5,1')
        ->name('2fa.challenge');

    Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])
        ->middleware('throttle:5,1')
        ->name('forgot-password');
});

// ─── Email Verification (public — signed URL from email) ────────────
Route::post('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
    ->middleware(['throttle:5,1'])
    ->name('api.verification.verify');

// ─── AUTHENTICATED (Sanctum) ──────────────────────────────────────────

Route::middleware('auth:sanctum')->group(function () {

    // ── Auth (logout) ────────────────────────────────────────────────
    Route::prefix('auth')->name('api.auth.')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
        Route::post('/logout-all', [AuthController::class, 'logoutAll'])->name('logout-all');
    });

    // ── Email Verification (authenticated) ──────────────────────────
    Route::post('/email/resend', [EmailVerificationController::class, 'resend'])
        ->middleware('throttle:5,1')
        ->name('api.verification.resend');

    // ── User & Profile ───────────────────────────────────────────────
    Route::get('/user', [AuthController::class, 'me'])->name('api.user');

    Route::prefix('profile')->name('api.profile.')->group(function () {
        Route::get('/', [ProfileController::class, 'show'])->name('show');
        Route::put('/', [ProfileController::class, 'update'])->name('update');
        Route::post('/change-password', [ProfileController::class, 'changePassword'])
            ->name('change-password');
    });

    // ── Face Recognition ─────────────────────────────────────────────
    Route::prefix('face')->name('api.face.')->group(function () {
        Route::post('/register', [FaceController::class, 'register'])
            ->middleware('throttle:10,1')
            ->name('register');
        Route::post('/verify', [FaceController::class, 'verify'])
            ->middleware('throttle:10,1')
            ->name('verify');
    });

    // ── Attendance ───────────────────────────────────────────────────
    Route::prefix('attendance')->name('api.attendance.')->group(function () {
        Route::post('/clock-in', [AttendanceController::class, 'clockIn'])
            ->middleware('throttle:5,5') // 5 per 5 menit
            ->name('clock-in');
        Route::post('/clock-out', [AttendanceController::class, 'clockOut'])
            ->middleware('throttle:5,5')
            ->name('clock-out');
        Route::get('/today', [AttendanceController::class, 'today'])->name('today');
        Route::get('/', [AttendanceController::class, 'index'])->name('index');
        Route::post('/{attendance}/approve-wfa', [AttendanceController::class, 'approveWfa'])
            ->name('approve-wfa');
    });

    // ── Leave ────────────────────────────────────────────────────────
    Route::prefix('leave')->name('api.leave.')->group(function () {
        Route::post('/', [LeaveController::class, 'store'])
            ->middleware('throttle:10,1')
            ->name('store');
        Route::get('/', [LeaveController::class, 'index'])->name('index');
        Route::get('/quota', [LeaveController::class, 'quota'])->name('quota');
        Route::get('/{leave}', [LeaveController::class, 'show'])->name('show');
        Route::delete('/{leave}', [LeaveController::class, 'destroy'])->name('destroy');
    });

    // ── Overtime ─────────────────────────────────────────────────────
    Route::prefix('overtime')->name('api.overtime.')->group(function () {
        Route::post('/', [OvertimeController::class, 'store'])
            ->middleware('throttle:10,1')
            ->name('store');
        Route::get('/', [OvertimeController::class, 'index'])->name('index');
        Route::get('/{overtime}', [OvertimeController::class, 'show'])->name('show');
        Route::delete('/{overtime}', [OvertimeController::class, 'destroy'])->name('destroy');
    });

    // ── Reimbursement ────────────────────────────────────────────────
    Route::prefix('reimbursement')->name('api.reimbursement.')->group(function () {
        Route::post('/', [ReimbursementController::class, 'store'])
            ->middleware('throttle:10,1')
            ->name('store');
        Route::get('/', [ReimbursementController::class, 'index'])->name('index');
        Route::get('/categories', [ReimbursementController::class, 'categories'])
            ->name('categories');
        Route::get('/{reimbursement}', [ReimbursementController::class, 'show'])->name('show');
        Route::patch('/{reimbursement}', [ReimbursementController::class, 'update'])
            ->name('update');
        Route::get('/{reimbursement}/receipt', [ReimbursementController::class, 'receipt'])
            ->name('receipt');
        Route::delete('/{reimbursement}', [ReimbursementController::class, 'destroy'])
            ->name('destroy');
    });

    // ── Approval Workflow ────────────────────────────────────────────
    Route::prefix('approvals')->name('api.approvals.')->group(function () {
        Route::get('/pending', [ApprovalController::class, 'pending'])->name('pending');
        Route::get('/history', [ApprovalController::class, 'history'])->name('history');
        Route::get('/{approval}', [ApprovalController::class, 'show'])->name('show');
        Route::post('/{approval}/approve', [ApprovalController::class, 'approve'])
            ->name('approve');
        Route::post('/{approval}/reject', [ApprovalController::class, 'reject'])
            ->name('reject');
    });

    // ── Payroll ──────────────────────────────────────────────────────
    Route::prefix('payroll')->name('api.payroll.')->group(function () {
        Route::get('/', [PayrollController::class, 'index'])->name('index');
        Route::post('/generate', [PayrollController::class, 'generate'])
            ->middleware('permission:process_payroll')
            ->name('generate');
        Route::post('/export/monthly', [PayrollController::class, 'exportMonthly'])
            ->middleware('permission:process_payroll')
            ->name('export.monthly');
        Route::post('/export/1721-a1', [PayrollController::class, 'export1721A1'])
            ->middleware('permission:process_payroll')
            ->name('export.1721-a1');
        Route::post('/export/bpjs', [PayrollController::class, 'exportBpjs'])
            ->middleware('permission:process_payroll')
            ->name('export.bpjs');
        Route::get('/{payroll}', [PayrollController::class, 'show'])->name('show');
        Route::get('/{payroll}/payslip', [PayrollController::class, 'payslip'])->name('payslip');
    });

    // ── Employee Directory (HR Manager + Super Admin) ────────────────
    Route::prefix('employees')->name('api.employees.')
        ->middleware('permission:view_employees')
        ->group(function () {
            Route::get('/', [EmployeeController::class, 'index'])->name('index');
            Route::get('/{employee}', [EmployeeController::class, 'show'])->name('show');
            Route::get('/{employee}/pii', [EmployeeController::class, 'showPii'])
                ->middleware('permission:manage_employees')
                ->name('pii');
            Route::post('/', [EmployeeController::class, 'store'])
                ->middleware('permission:manage_employees')
                ->name('store');
            Route::put('/{employee}', [EmployeeController::class, 'update'])
                ->middleware('permission:manage_employees')
                ->name('update');
            Route::delete('/{employee}', [EmployeeController::class, 'destroy'])
                ->middleware('permission:manage_employees')
                ->name('destroy');
            Route::post('/{employee}/terminate', [EmployeeTerminationController::class, 'terminate'])
                ->middleware('permission:manage_employees')
                ->name('terminate');
            Route::post('/terminate/contract-end', [EmployeeTerminationController::class, 'processContractEnd'])
                ->middleware('permission:manage_employees')
                ->name('terminate.contract-end');
        });

    // ── KnowledgeBase RAG (Sesi 11) ─────────────────────────────────
    Route::prefix('knowledgebase')->name('api.knowledgebase.')->group(function () {
        Route::post('/chat', [KnowledgeBaseController::class, 'chat'])
            ->middleware('throttle:20,1')
            ->name('chat');
        Route::post('/chat-stream', [KnowledgeBaseController::class, 'chatStream'])
            ->middleware('throttle:10,1')
            ->name('chat.stream');
        Route::get('/', [KnowledgeBaseController::class, 'index'])
            ->middleware('can:viewAny,'.KnowledgeBase::class)
            ->name('index');
        Route::post('/', [KnowledgeBaseController::class, 'upload'])
            ->middleware('permission:manage_knowledgebase')
            ->name('upload');
        Route::delete('/{knowledgeBase}', [KnowledgeBaseController::class, 'destroy'])
            ->middleware('permission:manage_knowledgebase')
            ->name('destroy');
    });
});

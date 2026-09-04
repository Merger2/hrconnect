<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\LeaveType;
use App\Models\Setting;
use App\Models\User;
use App\Services\Attendance\LeaveRequestService;
use App\Services\HR\LeaveService;
use App\Support\FileAccessService;
use App\Support\SecureUploadPolicy;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceController extends Controller
{
    public function __construct(
        protected LeaveRequestService $leaveRequestService,
        protected LeaveService $leaveService,
        protected FileAccessService $fileAccessService,
        protected SecureUploadPolicy $secureUploadPolicy,
    ) {}

    public function scan(): View
    {
        $this->authorize('create', Attendance::class);

        return view('attendances.scan');
    }

    public function applyLeave(Request $request): View
    {
        $this->authorize('create', Attendance::class);

        $user = $request->user();
        abort_unless($user instanceof User, 401);

        return view('attendances.apply-leave', $this->leaveRequestService->getApplyLeaveData($user));
    }

    public function storeLeaveRequest(Request $request): RedirectResponse
    {
        $this->authorize('create', Attendance::class);

        $user = $request->user();
        abort_unless($user instanceof User, 401);

        $leaveType = null;

        if ($request->filled('leave_type_id')) {
            $leaveType = LeaveType::query()
                ->active()
                ->findOrFail($request->integer('leave_type_id'));
        }

        $requireAttachment = Setting::getValue('leave.require_attachment', '1') === '1';
        $attachmentRequired = $requireAttachment || (bool) $leaveType?->requires_attachment;

        $request->validate([
            'leave_type_id' => ['nullable', 'integer', 'exists:leave_types,id'],
            'status' => ['required_without:leave_type_id', 'nullable', 'in:excused,sick'],
            'note' => ['required', 'string', 'max:255'],
            'from' => ['required', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'attachment' => [$attachmentRequired ? 'required' : 'nullable', ...$this->secureUploadPolicy->rules('document')],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        try {
            $employee = $user->employee;
            abort_unless($employee, 404, 'Employee profile not found.');

            $fromDate = Carbon::parse($request->string('from'));
            $toDate = Carbon::parse($request->input('to', $fromDate->toDateString()));

            // Determine leave type: explicit leave_type_id or map status code to leave type
            $resolvedLeaveType = $leaveType;
            if (! $resolvedLeaveType && $request->filled('status')) {
                $statusCode = $request->string('status')->toString();
                $sickCode = Setting::getValue('leave_sick_code', 'sick');
                $typeCode = $statusCode === 'sick' ? $sickCode : $statusCode;
                $resolvedLeaveType = LeaveType::where('code', $typeCode)->active()->first();

                if (! $resolvedLeaveType) {
                    return redirect()->back()
                        ->withInput()
                        ->with('error', __('Leave type not found for status: '.$statusCode));
                }
            }

            abort_unless($resolvedLeaveType, 422, 'Leave type is required.');

            $this->leaveService->applyLeave($employee, [
                'start_date' => $fromDate->toDateString(),
                'end_date' => $toDate->toDateString(),
                'leave_type_id' => $resolvedLeaveType->id,
                'day_type' => 'full_day',
                'reason' => $request->string('note')->toString(),
                'proof_file' => $request->file('attachment'),
            ]);

            return redirect(route('home'))
                ->with('success', __('Pengajuan izin berhasil dibuat.'));
        } catch (\Throwable $th) {
            Log::error('Failed to submit leave request.', [
                'user_id' => $user->getAuthIdentifier(),
                'exception' => $th->getMessage(),
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', $th->getMessage() ?: __('Terjadi kesalahan saat membuat pengajuan izin. Silakan coba lagi.'));
        }
    }

    public function downloadAttachment(Attendance $attendance): StreamedResponse
    {
        $this->authorize('view', $attendance);

        if (! $attendance->attachment) {
            abort(404);
        }

        return $this->fileAccessService->downloadRelativePath(
            $attendance->attachment,
            'Attendance Attachment Downloaded',
            'Downloaded attendance attachment'
        );
    }

    public function history(): View
    {
        $this->authorize('viewAny', Attendance::class);

        return view('attendances.history');
    }
}

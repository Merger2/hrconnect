<?php

namespace App\Http\Controllers\Api\Device;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\DeviceOfflineAttendanceSyncRequest;
use App\Models\User;
use App\Services\Attendance\OfflineAttendanceSyncService;
use Illuminate\Http\JsonResponse;

class OfflineAttendanceSyncController extends Controller
{
    public function __construct(
        private readonly OfflineAttendanceSyncService $offlineAttendanceSync,
    ) {}

    public function __invoke(DeviceOfflineAttendanceSyncRequest $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        return response()->json([
            'success' => true,
            'results' => $this->offlineAttendanceSync->sync($user, $request->validated('items')),
        ]);
    }
}

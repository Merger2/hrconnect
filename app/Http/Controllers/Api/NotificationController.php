<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Retrieve database notifications for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(ApiResponse::format(false, 401, 'Unauthorized', null), 401);
        }

        $notifications = $user->notifications()
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'title' => data_get($notification->data, 'title'),
                    'message' => data_get($notification->data, 'body') ?? data_get($notification->data, 'message'),
                    'status' => data_get($notification->data, 'status'),
                    'type' => data_get($notification->data, 'type'),
                    'data' => $notification->data,
                    'is_read' => $notification->read_at !== null,
                    'read_at' => optional($notification->read_at)->toDateTimeString(),
                    'created_at' => optional($notification->created_at)->toDateTimeString(),
                ];
            });

        return response()->json(ApiResponse::format(true, 200, 'Notifikasi berhasil diambil.', [
            'total' => $notifications->count(),
            'unread' => $user->unreadNotifications()->count(),
            'items' => $notifications,
        ]));
    }

    /**
     * Mark all notifications as read for the authenticated user.
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(ApiResponse::format(false, 401, 'Unauthorized', null), 401);
        }

        $updated = $user->unreadNotifications()->update([
            'read_at' => now(),
        ]);

        return response()->json(ApiResponse::format(true, 200, 'Seluruh notifikasi telah ditandai sebagai dibaca.', [
            'updated' => $updated,
        ]));
    }

    /**
     * Delete (dismiss) a single notification for the authenticated user.
     */
    public function destroy(Request $request, string $notification): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(ApiResponse::format(false, 401, 'Unauthorized', null), 401);
        }

        $notification = $user->notifications()->find($notification);

        if (! $notification) {
            return response()->json(ApiResponse::format(false, 404, 'Notifikasi tidak ditemukan.', null), 404);
        }

        $notification->delete();

        return response()->json(ApiResponse::format(true, 200, 'Notifikasi berhasil dihapus.', null));
    }
}

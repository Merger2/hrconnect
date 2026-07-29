<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SseNotificationController extends Controller
{
    public function stream(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['data' => []], 401);
        }

        $lastChecked = $request->query('since', now()->subMinutes(5)->toIso8601String());

        $notifications = $user->notifications()
            ->whereNull('read_at')
            ->where('created_at', '>', $lastChecked)
            ->limit(10)
            ->get()
            ->map(fn ($n) => [
                'id' => $n->id,
                'type' => data_get($n->data, 'type'),
                'message' => data_get($n->data, 'message', data_get($n->data, 'body')),
                'title' => data_get($n->data, 'title', 'Notification'),
                'created_at' => $n->created_at->toIso8601String(),
            ]);

        return response()->json(['data' => $notifications]);
    }
}

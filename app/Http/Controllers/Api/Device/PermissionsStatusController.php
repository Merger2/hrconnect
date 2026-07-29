<?php

namespace App\Http\Controllers\Api\Device;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class PermissionsStatusController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'permissions' => [
                'camera' => [
                    'state' => 'prompt',
                    'description' => 'Camera access for attendance photo',
                ],
                'geolocation' => [
                    'state' => 'prompt',
                    'description' => 'Location access for attendance tracking',
                ],
            ],
        ]);
    }
}

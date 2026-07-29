<?php

namespace App\Helpers;

class ApiResponse
{
    public static function format(bool $success, int $statusCode, string $message, mixed $data = null): array
    {
        $response = [
            'success' => $success,
            'message' => $message,
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        return $response;
    }
}

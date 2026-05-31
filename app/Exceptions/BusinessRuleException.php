<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

/**
 * BusinessRuleException — domain-level violation yang HARUS surface ke user.
 *
 * HTTP code: 422 Unprocessable Entity (sesuai AGENTS.md §11.0 + SEC-5).
 * Bukan 400 (Bad Request — biasanya untuk format/validation API level).
 */
class BusinessRuleException extends Exception
{
    public function render(): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'message' => $this->getMessage(),
        ], 422);
    }
}

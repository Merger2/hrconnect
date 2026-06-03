<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * BusinessRuleException — domain-level violation yang HARUS surface ke user.
 *
 * HTTP code: 422 Unprocessable Entity (sesuai AGENTS.md §11.0 + SEC-5).
 * Bukan 400 (Bad Request — biasanya untuk format/validation API level).
 */
class BusinessRuleException extends HttpException
{
    public function __construct(string $message = 'Aturan bisnis dilanggar.', ?\Throwable $previous = null)
    {
        parent::__construct(422, $message, $previous);
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'message' => $this->getMessage(),
        ], 422);
    }
}

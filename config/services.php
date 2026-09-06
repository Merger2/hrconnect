<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'gemini' => [
        'api_key' => env('GEMINI_API_KEY', env('GOOGLE_AI_API_KEY')),
        'model' => env('GEMINI_MODEL', 'gemini-flash-latest'),
        // 2026-08-06: text-embedding-004 dihapus Google (404 v1beta) —
        // gemini-embedding-001 + output_dimensionality 768 (selaras config/ai.php).
        'embedding_model' => env('GEMINI_EMBEDDING_MODEL', 'gemini-embedding-001'),
        'embedding_dimensions' => (int) env('GEMINI_EMBEDDING_DIMENSIONS', 768),
        'base_url' => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'),
        'timeout' => (int) env('GEMINI_TIMEOUT', 30),
        'max_retries' => (int) env('GEMINI_MAX_RETRIES', 3),
        // Hard gate AGENTS.md (cost limit): batas token AI per hari (global,
        // akumulasi di cache database via AiCostGuard). 0 = nonaktif.
        'daily_token_budget' => (int) env('AI_DAILY_TOKEN_BUDGET', 1_000_000),
    ],

];

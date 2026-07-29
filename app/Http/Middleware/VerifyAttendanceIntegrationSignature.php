<?php

namespace App\Http\Middleware;

use App\Models\IntegrationClient;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class VerifyAttendanceIntegrationSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        $providedApiKey = (string) $request->header('X-HRConnect-Api-Key', '');
        $timestamp = (string) $request->header('X-HRConnect-Timestamp', '');
        $signature = (string) $request->header('X-HRConnect-Signature', '');

        if ($providedApiKey === '') {
            return response()->json(['message' => 'Missing integration API key.'], 401);
        }

        $client = $this->findClient($providedApiKey);
        $secret = null;

        if ($client instanceof IntegrationClient) {
            if (! $client->isUsable()) {
                return response()->json(['message' => 'Integration client is not active.'], 401);
            }

            if (! $client->allowsIp($request->ip())) {
                return response()->json(['message' => 'Integration client is not allowed from this IP address.'], 403);
            }

            if (! $client->hasAbility(IntegrationClient::ABILITY_ATTENDANCE_WRITE)) {
                return response()->json(['message' => 'Integration client is not allowed to write attendance events.'], 403);
            }

            $secret = $client->secret();
            $request->attributes->set('integrationClient', $client);
        } else {
            $apiKey = (string) config('services.attendance_integration.api_key', '');
            $secret = (string) config('services.attendance_integration.secret', '');

            if (trim($apiKey) === '' || trim($secret) === '') {
                return response()->json(['message' => 'Attendance integration is not configured.'], 503);
            }

            if (! hash_equals($apiKey, $providedApiKey)) {
                return response()->json(['message' => 'Invalid integration API key.'], 401);
            }
        }

        if ($timestamp === '' || $signature === '') {
            return response()->json(['message' => 'Missing integration signature.'], 401);
        }

        if (! ctype_digit($timestamp)) {
            return response()->json(['message' => 'Invalid integration signature.'], 401);
        }

        $tolerance = (int) config('services.attendance_integration.signature_tolerance_seconds', 300);
        if (abs(time() - (int) $timestamp) > $tolerance) {
            return response()->json(['message' => 'Expired integration signature.'], 401);
        }

        $expected = 'sha256='.hash_hmac('sha256', $timestamp.'.'.$request->getContent(), $secret);

        if (! hash_equals($expected, $signature)) {
            return response()->json(['message' => 'Invalid integration signature.'], 401);
        }

        $client?->markUsed($request->ip());

        return $next($request);
    }

    private function findClient(string $apiKey): ?IntegrationClient
    {
        if (! Schema::hasTable('integration_clients')) {
            return null;
        }

        return IntegrationClient::query()
            ->where('api_key_hash', IntegrationClient::hashApiKey($apiKey))
            ->first();
    }
}

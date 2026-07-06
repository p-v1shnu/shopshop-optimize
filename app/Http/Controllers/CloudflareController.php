<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CloudflareController extends Controller
{
    public function flushCache(Request $request): JsonResponse
    {
        $expectedSecret = config('custom.flush_cache_secret');
        $providedSecret = $request->header('X-Secret');

        if (!$expectedSecret || !$providedSecret || !hash_equals($expectedSecret, $providedSecret)) {
            Log::info('Flush cache rejected: invalid secret', [
                'ip' => $request->ip(),
            ]);

            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $zoneId = config('custom.cloudflare_zone_id');
        $apiToken = config('custom.cloudflare_api_token');

        if (!$zoneId || !$apiToken) {
            Log::error('Flush cache misconfigured: missing Cloudflare zone id or api token');

            return response()->json(['message' => 'Cloudflare not configured'], 500);
        }

        $response = Http::withToken($apiToken)
            ->post("https://api.cloudflare.com/client/v4/zones/{$zoneId}/purge_cache", [
                'purge_everything' => true,
            ]);

        Log::info('Cloudflare purge cache response', [
            'status' => $response->status(),
            'body'   => $response->json(),
        ]);

        if (!$response->successful() || $response->json('success') !== true) {
            return response()->json([
                'message' => 'Failed to flush Cloudflare cache',
                'errors'  => $response->json('errors'),
            ], 502);
        }

        return response()->json(['message' => 'Cloudflare cache flushed']);
    }
}

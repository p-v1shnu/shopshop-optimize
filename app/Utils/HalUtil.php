<?php

namespace App\Utils;

use App\Models\ShippingLog;
use Exception;
use GuzzleHttp\TransferStats;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HalUtil
{
    /**
     * @throws Exception
     */
    public static function getToken(): string
    {
        $cacheKey = 'hal_access_token_client_id_' . config('custom.hal_client_id');

        if (cache()->has($cacheKey)) {
            return cache($cacheKey);
        }

        $requestInfo = [
            'url'    => 'https://hal.hal-logistics.la/oauth/token',
            'method' => 'POST',
            'data'   => [
                'grant_type'    => 'password',
                'client_id'     => config('custom.hal_client_id'),
                'client_secret' => config('custom.hal_client_secret'),
                'scope'         => '*',
                'username'      => config('custom.hal_username'),
                'password'      => config('custom.hal_password'),
            ]
        ];

        Log::info('HAL get token request', $requestInfo);

        $shippingLog = ShippingLog::create([
            'provider' => 'hal',
            'type'     => 'get_token',
            'data'     => [
                'request' => $requestInfo,
                'response' => null,
            ]
        ]);

        $responseTime = 0;

        $response = Http::withOptions([
            'http_errors' => false,
            'on_stats'    => function (TransferStats $stats) use (&$responseTime) {
                $responseTime = $stats->getTransferTime();
            }
        ])
        ->post($requestInfo['url'], $requestInfo['data']);

        $responseData = $response->json();
        $responseLog = [
            'statusCode' => $response->status(),
            'data'       => $responseData,
        ];

        Log::info('HAL get token response', $responseLog);

        $shippingLog->update([
            'data->response' => $responseLog,
            'response_time'  => $responseTime,
        ]);

        if ($response->successful()) {
            $accessToken = $responseData['access_token'];
            $cacheExpire = now()->addSeconds($responseData['expires_in'])->subMinutes(10);
            cache()->put($cacheKey, $accessToken, $cacheExpire);

            return $accessToken;
        }

        throw new Exception('Failed to get HAL access token');
    }
}

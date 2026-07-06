<?php

namespace App\Console\Commands;

use App\Models\ShippingLog;
use App\Utils\HalUtil;
use Exception;
use GuzzleHttp\TransferStats;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SubscribeHALWebhook extends Command
{
    protected $signature = 'SubscribeHALWebhook';

    public function handle(): void
    {
        try {
            $requestInfo = [
                'url'    => 'https://hal.hal-logistics.la/api/v1/auth/webhook-services/subscribe',
                'method' => 'POST',
                'headers' => [
                    'Authorization' => 'Bearer ' . HalUtil::getToken(),
                ],
                'data'   => [
                    'verify_secret' => config('custom.hal_verify_secret'),
                    'hook_url'      => config('custom.hal_webhook_url'),
                ]
            ];

            Log::info('HAL subscribe webhook request', $requestInfo);

            $shippingLog = ShippingLog::create([
                'provider' => 'hal',
                'type'     => 'subscribe_webhook',
                'data'     => [
                    'request'  => $requestInfo,
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
            ->withHeaders($requestInfo['headers'])
            ->post($requestInfo['url'], $requestInfo['data']);

            $responseData = $response->json();
            $responseLog = [
                'statusCode' => $response->status(),
                'data'       => $responseData,
            ];

            Log::info('HAL subscribe webhook response', $responseLog);

            $shippingLog->update([
                'data->response' => $responseLog,
                'response_time'  => $responseTime,
            ]);

            dump($responseData);

        } catch (Exception $e) {

            Log::error('HAL subscribe webhook error', [
                'message' => $e->getMessage(),
                'stack'   => $e->getTraceAsString(),
            ]);

            throw $e;

        }
    }
}

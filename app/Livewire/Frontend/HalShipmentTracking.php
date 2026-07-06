<?php

namespace App\Livewire\Frontend;

use App\Models\ShippingLog;
use App\Models\ShopOrder;
use App\Utils\HalUtil;
use Exception;
use GuzzleHttp\TransferStats;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

class HalShipmentTracking extends Component
{
    #[Locked]
    public ShopOrder $order;

    #[Locked]
    public array $trackingResponseData = [];

    public function __construct()
    {
        $this->trackingResponseData = [];
    }

    public function mount(): void
    {
        $this->fetchTracking();
    }

    public function render()
    {
        return view('frontend.livewire.hal-shipment-tracking');
    }

    public function placeholder(array $params = []): View
    {
        return view('frontend.livewire.hal-shipment-tracking-placeholder', $params);
    }

    public function fetchTracking(): void
    {
        try {

            // Cache for 5 minutes
            $this->trackingResponseData = Cache::remember('hal_shipping_tracking_api_response_' . $this->order->shipping_tracking_number, 300, function () {
                $requestInfo = [
                    'url'    => 'https://hal.hal-logistics.la/api/v1/orders/tracking/' . $this->order->shipping_tracking_number,
                    'method' => 'GET',
                    'data'   => [],
                    'headers' => [
                        'Authorization' => 'Bearer ' . HalUtil::getToken(),
                        'Locale'        => 'lo',
                    ]
                ];

                Log::info('HAL get tracking request', $requestInfo);

                $shippingLog = ShippingLog::create([
                    'provider' => 'hal',
                    'type'     => 'get_tracking',
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
                ->acceptJson()
                ->get($requestInfo['url'], $requestInfo['data']);

                $responseData = $response->json();
                $responseLog = [
                    'statusCode' => $response->status(),
                    'data'       => $responseData,
                ];

                Log::info('HAL get tracking response', $responseLog);

                $shippingLog->update([
                    'data->response' => $responseLog,
                    'response_time'  => $responseTime,
                ]);

                return $responseData;
            });

        } catch (Exception $e) {

            Log::error('Fetch HAL tracking error', [
                'message' => $e->getMessage(),
                'stack'   => $e->getTraceAsString(),
            ]);

            $this->dispatch('openModal', 'alert-modal', [
                'type'    => 'error',
                'message' => 'ບໍ່ສາມາດດຶງການເຄື່ອນໄຫວຂອງພັດສະດຸໄດ້',
            ]);

        }
    }
}

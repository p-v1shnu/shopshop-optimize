<?php

namespace App\Http\Controllers;

use App\Models\ShippingLog;
use App\Models\ShopOrder;
use App\Models\ShopOrderLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class HALController extends Controller
{
    public function webhookGet(Request $request)
    {
        $requestLogData = [
            'url'     => $request->fullUrl(),
            'method'  => $request->method(),
            'headers' => $request->headers->all(),
            'body'    => $request->json()->all(),
            'query'   => $request->query->all(),
        ];

        Log::info('HAL Webhook GET request', $requestLogData);

        $shippingLog = ShippingLog::create([
            'provider' => 'hal',
            'type'     => 'webhook_get',
            'data'     => [
                'request'  => $requestLogData,
                'response' => null,
            ]
        ]);

        $verifySecret = config('custom.hal_verify_secret');
        $receivedVerifySecret = $request->query('x_verify_secret');
        $receivedChallengingId = $request->query('x_challenging_id');

        if (!$receivedVerifySecret) {
            Log::info('HAL Webhook Missing verification secret');

            $shippingLog->update([
                'data->response' => [
                    'message' => 'HAL Webhook Missing verification secret',
                    'data' => []
                ]
            ]);

            return response('Missing verification secret', 400);
        }

        if ($receivedVerifySecret !== $verifySecret) {
            Log::info('HAL Webhook Invalid verification secret', [
                'verifySecret'         => $verifySecret,
                'receivedVerifySecret' => $receivedVerifySecret,
            ]);

            $shippingLog->update([
                'data->response' => [
                    'message' => 'HAL Webhook Invalid verification secret',
                    'data' => [
                        'verifySecret'         => $verifySecret,
                        'receivedVerifySecret' => $receivedVerifySecret,
                    ]
                ]
            ]);

            return response('Invalid verification secret', 400);
        }

        Log::info('HAL Webhook Challenging ID', [
            'receivedChallengingId' => $receivedChallengingId,
        ]);

        $shippingLog->update([
            'data->response' => [
                'message' => 'HAL Webhook Challenging ID',
                'data' => [
                    'receivedChallengingId' => $receivedChallengingId,
                ]
            ]
        ]);

        return response($receivedChallengingId, 200);
    }

    public function webhookPost(Request $request)
    {
        $requestLogData = [
            'url'     => $request->fullUrl(),
            'method'  => $request->method(),
            'headers' => $request->headers->all(),
            'body'    => $request->json()->all(),
            'query'   => $request->query->all(),
        ];

        Log::info('HAL Webhook POST request', $requestLogData);

        $payload = $request->all();
        $shipmentNumber = $payload['changes']['shipment']['shipment_number'] ?? null;

        $shippingLog = ShippingLog::create([
            'provider'           => 'hal',
            'provider_reference' => $shipmentNumber,
            'type'               => 'webhook_post',
            'data'               => [
                'request'  => $requestLogData,
                'response' => null,
            ]
        ]);

        $signSecret = config('custom.hal_sign_secret');

        if (!$signSecret) {
            Log::info('HAL Webhook Signing secret is not configured');

            $shippingLog->update([
                'data->response' => [
                    'message' => 'Signing secret is not configured',
                    'data' => []
                ]
            ]);

            return response('Signing secret is not configured', 500);
        }

        $payloadString = json_encode($request->all());
        $generatedSignature = hash_hmac('sha256', $payloadString, $signSecret);
        $receivedSignature = $request->header('signature');

        if (!$receivedSignature) {
            Log::info('HAL Webhook Signature is missing', [
                'generatedSignature' => $generatedSignature,
                'receivedSignature'  => $receivedSignature,
            ]);

            $shippingLog->update([
                'data->response' => [
                    'message' => 'HAL Webhook Signature is missing',
                    'data' => [
                        'generatedSignature' => $generatedSignature,
                        'receivedSignature'  => $receivedSignature,
                    ]
                ]
            ]);

            return response('Signature is missing', 400);
        }

        if (! hash_equals($generatedSignature, $receivedSignature)) {
            Log::info('HAL Webhook Signature mismatch', [
                'generatedSignature' => $generatedSignature,
                'receivedSignature'  => $receivedSignature,
            ]);

            $shippingLog->update([
                'data->response' => [
                    'message' => 'HAL Webhook Signature mismatch',
                    'data' => [
                        'generatedSignature' => $generatedSignature,
                        'receivedSignature'  => $receivedSignature,
                    ]
                ]
            ]);

            return response('Signature mismatch', 403);
        }

        Log::info('HAL Webhook Verified payload', $payload);

        // ================================================================================

        if ($payload['changes']['action'] === 'updated') {
            $order = ShopOrder::query()
                ->where('shipping_tracking_number', '=', $shipmentNumber)
                ->first();

            if (!$order) {
                $shippingLog->update([
                    'data->response' => [
                        'message' => 'Order not found for update shipping status',
                        'data' => []
                    ]
                ]);

                return response()->json(['message' => 'Order not found for update shipping status']);
            }

            $statusId = $payload['changes']['shipment']['shipment_status_id'];

            if ($statusId === 1) {
                $order->update(['shipping_status' => 'pending']);

                ShopOrderLog::create([
                    'tenant_id'     => $order->tenant_id,
                    'shop_order_id' => $order->id,
                    'type'          => 'update_shipping_status',
                    'detail'        => [
                        'status' => 'pending',
                    ],
                ]);
            }
            else if ($statusId === 2) {
                $order->update(['shipping_status' => 'shipping']);

                ShopOrderLog::create([
                    'tenant_id'     => $order->tenant_id,
                    'shop_order_id' => $order->id,
                    'type'          => 'update_shipping_status',
                    'detail'        => [
                        'status' => 'shipping',
                    ],
                ]);
            }
            else if ($statusId === 3) {
                $order->update(['shipping_status' => 'completed']);

                ShopOrderLog::create([
                    'tenant_id'     => $order->tenant_id,
                    'shop_order_id' => $order->id,
                    'type'          => 'update_shipping_status',
                    'detail'        => [
                        'status' => 'completed',
                    ],
                ]);
            }
        }

        // ================================================================================

        return response()->json(['message' => 'ok']);
    }
}

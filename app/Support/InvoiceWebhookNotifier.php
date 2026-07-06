<?php

namespace App\Support;

use App\Models\ShopOrder;
use App\Models\ShopOrderLog;
use App\Models\ShopProduct;
use App\Utils\FormUtil;
use App\Utils\WebhookUtil;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class InvoiceWebhookNotifier
{
    public function notify(ShopOrder $order): ShopOrderLog
    {
        $order->loadMissing([
            'tenant:id,name,delivery_contact_phone,order_invoice_webhook_url',
            'details:id,shop_order_id,shop_product_id,quantity',
        ]);

        if (blank($order->tenant?->order_invoice_webhook_url)) {
            throw new Exception('Order invoice webhook URL is not configured.');
        }

        $requestInfo = $this->requestInfo($order);

        Log::info('Notify invoice API request', [
            'shop_order_id' => $order->id,
            'request' => $requestInfo,
        ]);

        $shopOrderLog = ShopOrderLog::query()->create([
            'tenant_id' => $order->tenant_id,
            'shop_order_id' => $order->id,
            'type' => 'notify_invoice_api',
            'detail' => [
                'request' => $requestInfo,
                'response' => [],
            ],
        ]);

        $response = Http::send($requestInfo['method'], $requestInfo['url'], [
            'headers' => $requestInfo['headers'],
            'json' => $requestInfo['data'],
        ]);

        $responseLog = [
            'status' => $response->status(),
            'data' => $response->body(),
            'timestamp' => now()->toDateTimeString(),
        ];

        Log::info('Notify invoice API response', [
            'shop_order_id' => $order->id,
            'response' => $responseLog,
        ]);

        $responseTime = $response->transferStats
            ? (float) sprintf('%.3f', $response->transferStats->getTransferTime())
            : 0;

        $logDetail = $shopOrderLog->detail;
        $logDetail['response'] = $responseLog;
        $logDetail['response_time'] = $responseTime;

        $shopOrderLog->update([
            'detail' => $logDetail,
            'response_time' => $responseTime,
        ]);

        $order->forceFill([
            'notified_invoice_api_at' => now(),
        ])->save();

        return $shopOrderLog->refresh();
    }

    private function requestInfo(ShopOrder $order): array
    {
        $data = $this->payload($order);
        $jsonData = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $data['signature'] = WebhookUtil::generateSignature($jsonData);

        return [
            'url' => $order->tenant->order_invoice_webhook_url,
            'method' => 'POST',
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'data' => $data,
            'timestamp' => now()->toDateTimeString(),
        ];
    }

    private function payload(ShopOrder $order): array
    {
        [$deliveryService, $deliveryBranch, $deliveryBranchPhone] = $this->deliveryInfo($order);

        return [
            'tenant_id' => $order->tenant_id,
            'delivery_phone' => $order->tenant->delivery_contact_phone,
            'brands' => $order->tenant->name,
            'order_id' => $order->id,
            'order_code' => $order->order_code,
            'order_date' => $order->created_at->format('d/m/Y H:i:s'),
            'coupon_amount' => (float) $order->coupon_amount,
            'grand_total_amount' => (float) $order->payment_amount,
            'customer_name' => $order->shipping_name,
            'customer_phone' => '856'.$order->shipping_phone,
            'customer_address' => 'ຂ.'.FormUtil::getProvinceName($order->shipping_province).' ມ.'.$order->shipping_district.' ບ.'.$order->shipping_village,
            'delivery_service' => $deliveryService,
            'delivery_branch' => $deliveryBranch,
            'delivery_branch_phone' => $deliveryBranchPhone,
            'shipment_number' => $order->shipping_tracking_number,
            'shipping_fee_type' => $order->shipping_fee_type,
            'shipping_remark' => $order->shipping_remark,
            'shipping_channel' => $order->shipping_channel,
            'order_slug' => ltrim(route('shop.orderDetail', ['orderId' => $order->id], false), '/'),
            'order_details' => $this->orderDetails($order),
        ];
    }

    private function orderDetails(ShopOrder $order): array
    {
        return $order->details
            ->groupBy('shop_product_id')
            ->map(function ($items): array {
                $productId = $items->first()['shop_product_id'];
                $product = ShopProduct::query()
                    ->select(['id', 'name', 'price', 'total_unit', 'unit_type', 'storage', 'short_description', 'long_description'])
                    ->find($productId);

                if (! $product) {
                    throw new Exception('Product item not found. Code: '.$productId);
                }

                return [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_price' => (float) $product->price,
                    'product_total_unit' => $product->total_unit,
                    'product_unit_type' => $product->unit_type,
                    'product_storage' => $product->storage,
                    'product_short_description' => $product->short_description,
                    'product_long_description' => $product->long_description,
                    'quantity' => $items->sum('quantity'),
                ];
            })
            ->values()
            ->toArray();
    }

    private function deliveryInfo(ShopOrder $order): array
    {
        if ($order->shipping_channel !== 'hal') {
            return ['ອື່ນໆ', null, null];
        }

        $deliveryBranch = 'ແຂວງ. '.$order->shipping_branch_province.' ເມືອງ. '.$order->shipping_branch_district.' ສາຂາ. '.$order->shipping_branch_name;
        $tel = data_get($order->shipping_detail, 'pre_order.end_branch.tel');
        $deliveryBranchPhone = $tel
            ? (str_starts_with($tel, '20') ? '856'.$tel : '85620'.$tel)
            : null;

        return ['ຮຸ່ງອາລຸນ ຂົນສົ່ງດ່ວນ', $deliveryBranch, $deliveryBranchPhone];
    }
}

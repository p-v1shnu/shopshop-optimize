<?php

namespace App\Console\Commands;

use App\Models\ShopOrder;
use App\Models\ShopOrderLog;
use App\Models\ShopProduct;
use App\Utils\FormUtil;
use App\Utils\WebhookUtil;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendInvoiceWebhook extends Command
{
    protected $signature = 'SendInvoiceWebhook';

    public function handle(): void
    {
        $orders = ShopOrder::query()
            ->select([
                'tenant_id', 'id', 'order_code', 'created_at', 'payment_amount', 'notified_invoice_api_at',
                'shipping_channel', 'shipping_name', 'shipping_phone', 'shipping_province',
                'shipping_district', 'shipping_village', 'shipping_tracking_number',
                'shipping_branch_province', 'shipping_branch_district', 'shipping_branch_name',
                'coupon_amount', 'shipping_fee_type', 'shipping_remark', 'shipping_detail'
            ])
            ->with([
                'tenant' => function ($query) {
                    $query->select(['id', 'name', 'delivery_contact_phone', 'order_invoice_webhook_url']);
                },
                'details' => function ($query) {
                    $query->select(['id', 'shop_order_id', 'shop_product_id', 'quantity']);
                }
            ])
            ->whereNull('notified_invoice_api_at')
            ->where('payment_status', '=', 'paid')
            ->whereRelation('tenant', function ($query) {
                $query
                    ->whereNotNull('delivery_contact_phone')
                    ->whereNotNull('name')
                    ->whereNotNull('order_invoice_webhook_url');
            })
            ->orderBy('created_at')
            ->take(20)
            ->get();

        $this->info('Total order to notify: ' . number_format($orders->count()));

        foreach ($orders as $order) {
            try {

                // Group data
                $orderDetails = $order->details
                    ->groupBy('shop_product_id')
                    ->map(function ($items) {
                        $productId = $items->first()['shop_product_id'];
                        $product = ShopProduct::query()
                            ->select(['id', 'name', 'price', 'total_unit', 'unit_type', 'storage', 'short_description', 'long_description'])
                            ->find($productId);

                        if (!$product) {
                            throw new Exception('Product item not found. Code: ' . $productId);
                        }

                        return [
                            'product_id'                => $product->id,
                            'product_name'              => $product->name,
                            'product_price'             => (float) $product->price,
                            'product_total_unit'        => $product->total_unit,
                            'product_unit_type'         => $product->unit_type,
                            'product_storage'           => $product->storage,
                            'product_short_description' => $product->short_description,
                            'product_long_description'  => $product->long_description,
                            'quantity'                  => $items->sum('quantity'),
                        ];
                    })
                    ->values()
                    ->toArray();

                $deliveryService = null;
                $deliveryBranch = null;
                $deliveryBranchPhone = null;

                if ($order->shipping_channel === 'hal') {
                    $deliveryService = 'ຮຸ່ງອາລຸນ ຂົນສົ່ງດ່ວນ';
                    $deliveryBranch = 'ແຂວງ. ' . $order->shipping_branch_province . ' ເມືອງ. ' . $order->shipping_branch_district . ' ສາຂາ. ' . $order->shipping_branch_name;
                    $tel = $order->shipping_detail['pre_order']['end_branch']['tel'];
                    $deliveryBranchPhone = str_starts_with($tel, '20') ? '856' . $tel : '85620' . $tel;
                } else {
                    $deliveryService = 'ອື່ນໆ';
                }

                // Prepare data
                $data = [
                    'tenant_id'             => $order->tenant_id,
                    'delivery_phone'        => $order->tenant->delivery_contact_phone,
                    'brands'                => $order->tenant->name,
                    'order_id'              => $order->id,
                    'order_code'            => $order->order_code,
                    'order_date'            => $order->created_at->format('d/m/Y H:i:s'),
                    'coupon_amount'         => (float) $order->coupon_amount,
                    'grand_total_amount'    => (float) $order->payment_amount,
                    'customer_name'         => $order->shipping_name,
                    'customer_phone'        => '856' . $order->shipping_phone,
                    'customer_address'      => 'ຂ.' . FormUtil::getProvinceName($order->shipping_province) . ' ມ.' . $order->shipping_district . ' ບ.' . $order->shipping_village,
                    'delivery_service'      => $deliveryService,
                    'delivery_branch'       => $deliveryBranch,
                    'delivery_branch_phone' => $deliveryBranchPhone,
                    'shipment_number'       => $order->shipping_tracking_number,
                    'shipping_fee_type'     => $order->shipping_fee_type,
                    'shipping_remark'       => $order->shipping_remark,
                    'shipping_channel'      => $order->shipping_channel,
                    'order_slug'            => ltrim(route('shop.orderDetail', ['orderId' => $order->id], false), '/'),
                    'order_details'         => $orderDetails,
                ];

                $jsonData = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                $data['signature'] = WebhookUtil::generateSignature($jsonData);

                // ================================================================================

                $requestInfo = [
                    'url'     => $order->tenant->order_invoice_webhook_url,
                    'method'  => 'POST',
                    'headers' => [
                        'Content-Type' => 'application/json',
                    ],
                    'data'      => $data,
                    'timestamp' => now()->toDateTimeString(),
                ];

                Log::info('Notify invoice API request', [
                    'shop_order_id' => $order->id,
                    'request'       => $requestInfo,
                ]);

                $shopOrderLog = ShopOrderLog::create([
                    'tenant_id'     => $order->tenant_id,
                    'shop_order_id' => $order->id,
                    'type'          => 'notify_invoice_api',
                    'detail'        => [
                        'request'  => $requestInfo,
                        'response' => [],
                    ],
                ]);

                // ================================================================================

                $response = Http::send($requestInfo['method'], $requestInfo['url'], [
                    'headers' => $requestInfo['headers'],
                    'json'    => $requestInfo['data'],
                ]);

                $responseLog = [
                    'status'    => $response->status(),
                    'data'      => $response->body(),
                    'timestamp' => now()->toDateTimeString(),
                ];

                Log::info('Notify invoice API response', [
                    'shop_order_id' => $order->id,
                    'response'      => $responseLog,
                ]);

                $responseTime = $response->transferStats
                    ? (float)sprintf("%.3f", $response->transferStats->getTransferTime())
                    : 0;

                $logDetail = $shopOrderLog->detail;
                $logDetail['response'] = $responseLog;
                $logDetail['response_time'] = $responseTime;

                $shopOrderLog->update([
                    'detail'        => $logDetail,
                    'response_time' => $responseTime,
                ]);

                // ================================================================================

                $order->notified_invoice_api_at = now();
                $order->save();

                $this->info('Order invoice API notified. Order ID: ' . $order->id);

            } catch (Exception $e) {

                Log::error('Notify invoice API error', [
                    'message' => $e->getMessage(),
                    'stack'   => $e->getTraceAsString(),
                ]);

                throw $e;

            }
        }

        $this->info('Finish');
    }
}

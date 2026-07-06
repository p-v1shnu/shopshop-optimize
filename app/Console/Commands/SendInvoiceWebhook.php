<?php

namespace App\Console\Commands;

use App\Models\ShopOrder;
use App\Support\InvoiceWebhookNotifier;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendInvoiceWebhook extends Command
{
    protected $signature = 'SendInvoiceWebhook';

    public function handle(InvoiceWebhookNotifier $notifier): void
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
                $notifier->notify($order);

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

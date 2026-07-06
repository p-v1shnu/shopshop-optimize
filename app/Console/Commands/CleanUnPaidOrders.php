<?php

namespace App\Console\Commands;

use App\Models\ShopCoupon;
use App\Models\ShopOrder;
use App\Models\ShopOrderLog;
use App\Models\ShopProduct;
use App\Models\ShopProductStock;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanUnPaidOrders extends Command
{
    protected $signature = 'CleanUnPaidOrders';

    public function handle(): void
    {
        $orders = ShopOrder::query()
            ->with(['details:id,shop_order_id,shop_product_id,quantity', 'coupons'])
            ->select(['id', 'tenant_id', 'payment_status', 'payment_expired_at', 'created_at'])
            ->where('payment_status', '=', 'pending')
            ->where('payment_expired_at', '<', now())
            ->orderBy('created_at')
            ->get();

        $this->info("Total orders to be cleaned: {$orders->count()}");

        foreach ($orders as $order) {
            DB::transaction(function () use ($order) {
                // Reload order within transaction with lock to prevent race conditions
                $lockedOrder = ShopOrder::query()
                    ->with(['details:id,shop_order_id,shop_product_id,quantity', 'coupons'])
                    ->where('id', $order->id)
                    ->lockForUpdate()
                    ->first();

                // Skip if already processed
                if (!$lockedOrder || $lockedOrder->payment_status !== 'pending') {
                    return;
                }

                // Return product quantities back to inventory
                foreach ($lockedOrder->details as $detail) {
                    $product = ShopProduct::query()
                        ->where('id', '=', $detail->shop_product_id)
                        ->lockForUpdate()
                        ->first();

                    if ($product) {
                        // Increment available quantity
                        $product->increment('available_quantity', $detail->quantity);

                        // Create stock record
                        ShopProductStock::create([
                            'tenant_id'       => $lockedOrder->tenant_id,
                            'shop_order_id'   => $lockedOrder->id,
                            'shop_product_id' => $product->id,
                            'quantity'        => $detail->quantity,
                            'remark'          => 'Order expired',
                            'xref'            => 'order_expired_' . $lockedOrder->id . '_' . $product->id,
                        ]);
                    }
                }

                // Return coupon quantity back to inventory
                foreach ($lockedOrder->coupons as $orderCoupon) {
                    $coupon = ShopCoupon::query()
                        ->where('id', '=', $orderCoupon->shop_coupon_id)
                        ->lockForUpdate()
                        ->first();

                    if ($coupon) {
                        $coupon->increment('available_quantity');
                    }
                }

                $lockedOrder->update([
                    'payment_status' => 'expired',
                ]);

                ShopOrderLog::create([
                    'tenant_id'     => $lockedOrder->tenant_id,
                    'shop_order_id' => $lockedOrder->id,
                    'type'          => 'mark_expired',
                ]);

                $this->info("Order $lockedOrder->id has been marked as expired.");
            });
        }
    }
}

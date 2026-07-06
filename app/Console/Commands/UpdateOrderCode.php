<?php

namespace App\Console\Commands;

use App\Models\ShopOrder;
use Illuminate\Console\Command;

class UpdateOrderCode extends Command
{
    protected $signature = 'UpdateOrderCode';

    public function handle(): void
    {
        $orders = ShopOrder::query()
            ->whereNull('order_code')
            ->orderBy('created_at')
            ->get();

        foreach ($orders as $order) {
            $order->order_code = ShopOrder::generateOrderCode();
            $order->save();
        }
    }
}

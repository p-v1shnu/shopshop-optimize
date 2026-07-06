<?php

namespace App\Console\Commands;

use App\Models\ShopOrder;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Log;

class ReconcileOnepay extends Command
{
    protected $signature = 'ReconcileOnepay';

    public function handle(): void
    {
        $orders = ShopOrder::query()
            ->where('payment_status', '=', 'pending')
            ->whereDate('payment_expired_at', '<=', CarbonImmutable::now())
            ->whereNull('payment_reconciled_at')
            ->orderBy('created_at')
            ->get();

        $this->info('Total orders: ' . $orders->count());
        $i = 1;

        foreach ($orders as $order) {
            $mcid = config('custom.bcel_qr_mc_id');
            $uuid = config('custom.payment_uuid_prefix') . $order->id;
            $url = "https://bcel.la:8083/onepay/gettransaction.php?mcid=$mcid&uuid=$uuid";

            $paymentResponse = Http::get($url);
            Log::info('Reconcile Payment response', [
                'order_id' => $order->id,
                'mcid'     => $mcid,
                'uuid'     => $uuid,
                'url'      => $url,
                'response' => $paymentResponse->json(),
            ]);

            if ($paymentResponse->successful()) {
                $this->info($i . ' - ID: ' . $order->id . ' - Paid');
                $updateResponse = Http::post(route('api.webhook.updateOnepay'), $paymentResponse->json());
                Log::info('Reconcile Update response', [
                    'order_id' => $order->id,
                    'response' => $updateResponse->json(),
                ]);
            } else {
                $this->warn($i . ' - ID: ' . $order->id . ' - NOT PAID');
                $order->payment_reconciled_at = CarbonImmutable::now();
                $order->save();
            }

            $i++;
        }
    }
}

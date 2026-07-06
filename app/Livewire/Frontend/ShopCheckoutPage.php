<?php

namespace App\Livewire\Frontend;

use App\Models\ShopOrder;
use Exception;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

class ShopCheckoutPage extends Component
{
    #[Locked]
    public ShopOrder $order;

    public function mount(string $orderId)
    {
        $order = ShopOrder::query()
            ->select([
                'id', 'payment_status', 'payment_channel', 'generate_qr_response', 'order_amount', 'coupon_amount', 'payment_amount',
                'shipping_name', 'shipping_phone', 'shipping_branch_province', 'shipping_branch_district', 'shipping_branch_name',
                'shipping_province', 'shipping_district', 'shipping_village',
                'shipping_amount', 'shipping_channel', 'shipping_fee_type'
            ])
            ->with(['details.product' => function ($query) {
                $query
                    ->select(['id', 'name', 'normal_price', 'price', 'images'])
                    ->orderBy('id');
            }])
            ->where('id', '=', $orderId)
            ->first();

        if (!$order) {
            return $this->redirect(route('shop.cart'));
        }

        $this->order = $order;

        if ($this->order->payment_status !== 'pending') {

            // Clear session when paid and user refresh the page
            if ($this->order->payment_status === 'paid') {
                session()->forget('cartProducts');
                session()->forget('paymentChannel');
                session()->forget('cartCoupon');
            }

            return $this->redirect(route('shop.orderDetail', ['orderId' => $this->order->id]));
        }
    }

    public function render()
    {
        if ($this->order->payment_channel === 'bcel') {
            $view = 'frontend.livewire.shop-checkout-bcel-page';
        }
        else if ($this->order->payment_channel === 'jdb') {
            $view = 'frontend.livewire.shop-checkout-jdb-page';
        }
        else if ($this->order->payment_channel === 'laoqr') {
            $view = 'frontend.livewire.shop-checkout-laoqr-page';
        }
        else {
            throw new Exception('Unknown payment method');
        }

        return view($view)
            ->layout('frontend.livewire.layout', [
                'showNavbar' => true,
                'showFooter' => false,
                'footerJs' => [
                    'resources/js/echo.js',
                ],
            ])
            ->title('ຈ່າຍເງິນ');
    }

    #[On('echo-private:orders.{order.id},OrderPaid')]
    public function showPaymentSuccess(): void
    {
        $this->order->refresh();

        if ($this->order->payment_status !== 'paid') {
            return;
        }

        $this->dispatch('openModal', 'alert-modal', [
            'type'       => 'success',
            'message'    => 'ການສັ່ງຊື້ສຳເລັດ',
            'buttonText' => 'ເບິ່ງລາຍລະອຽດຄຳສັ່ງຊື້',
            'buttonLink' => "/shop/orders/{$this->order->id}?showPaidBanner=true",
        ]);

        // Hand the paid order to GTM (dataLayer) for the GA4/Facebook "purchase" event.
        $user = auth()->user();

        $this->order->loadMissing('details');

        $items = $this->order->details->map(fn ($detail) => [
            'item_id'   => $detail->shop_product_id,
            'item_name' => $detail->name,
            'price'     => (float) $detail->price,
            'quantity'  => (int) $detail->quantity,
        ])->values()->all();

        $this->dispatch('order-paid',
            order: [
                'id'               => $this->order->id,
                'created_at'       => $this->order->created_at?->format('Y-m-d H:i:s'),
                'campaign_code'    => $this->order->campaign_code,
                'value'            => (float) $this->order->payment_amount,
                'currency'         => 'LAK',
                'shipping_channel' => $this->order->shipping_channel,
                'items'            => $items,
            ],
            user: [
                'id'         => $user->id,
                'gender'     => $user->gender,
                'first_name' => $user->name,
                'last_name'  => null,
                'phone'      => $user->phone,
                'province'   => $user->province,
            ],
        );

        try {
            if (app()->isProduction()) {
                $this->js("(function() { try { window.fbq('track', 'Purchase', { currency: 'LAK', value: " . $this->order->payment_amount . ", orderId: '" . $this->order->id . "' }); } catch(e) { console.error('FB Pixel error:', e); } })()");
            }
        } catch (Exception $e) {
            Log::error('Facebook pixel track error', [
                'message' => $e->getMessage(),
                'stack'   => $e->getTraceAsString(),
                'event'   => 'Order Paid',
            ]);
        }

        session()->forget('cartProducts');
        session()->forget('paymentChannel');
        session()->forget('cartCoupon');
    }
}

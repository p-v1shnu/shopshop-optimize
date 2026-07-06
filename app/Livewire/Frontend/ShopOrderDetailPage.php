<?php

namespace App\Livewire\Frontend;

use App\Models\ShopOrder;
use Livewire\Attributes\Locked;
use Livewire\Component;

class ShopOrderDetailPage extends Component
{
    #[Locked]
    public ShopOrder $order;

    public $showPaidBanner = false;

    protected $queryString = [
        'showPaidBanner' => ['except' => ''],
    ];

    public function mount(string $orderId): void
    {
        $order = ShopOrder::query()
            ->select([
                'id', 'created_at', 'shipping_detail', 'payment_channel', 'payment_status', 'shipping_tracking_number', 'payment_uuid',
                'shipping_amount', 'shipping_fee_type', 'shipping_name', 'shipping_phone', 'shipping_amount', 'payment_amount',
                'shipping_province', 'shipping_district', 'shipping_village', 'shipping_channel',
                'order_amount', 'coupon_amount', 'order_code'
            ])
            ->with(['payment' => function ($query) {
                $query->select(['id', 'shop_order_id', 'ref']);
            }])
            ->with(['details.product' => function ($query) {
                $query
                    ->select(['id', 'name', 'price', 'images'])
                    ->orderBy('id');
            }])
            ->where('id', '=', $orderId)
            ->where('user_id', '=', auth()->user()->id)
            ->first();

        if (!$order) {
            abort(404, 'ບໍ່ພົບຄຳສັ່ງຊື້ ' . $orderId);
        }

        $this->order = $order;
    }

    public function render()
    {
        return view('frontend.livewire.shop-order-detail-page')
            ->layout('frontend.livewire.layout', [
                'showNavbar' => true,
                'showFooter' => true,
                'backUrl'    => route('shop.orders'),
            ])
            ->title('ລາຍລະອຽດຄຳສັ່ງຊື້');
    }
}

<?php

namespace App\Livewire\Frontend;

use App\Models\ShopOrder;
use App\Utils\ShopUtil;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Locked;
use Livewire\Component;

class ShopOrdersPage extends Component
{
    #[Locked]
    public Collection $orders;

    #[Locked]
    public string $tab = 'pending';

    public $shippingStatus = 'pending';

    protected $queryString = [
        'shippingStatus' => ['except' => ''], // Avoid cluttering URL when empty
    ];

    public function mount(): void
    {
        $shippingChannels = tenant('shipping_channels') ?? [];

        // If the tenant only has "seller" as shipping channel, orders are always "completed",
        // so default to "completed" tab instead of "pending".
        if ($this->shippingStatus === 'pending' && $shippingChannels === ['seller']) {
            $this->shippingStatus = 'completed';
        }

        $this->changeTab($this->shippingStatus);
    }

    public function render()
    {
        return view('frontend.livewire.shop-orders-page')
            ->layout('frontend.livewire.layout', [
                'showNavbar' => true,
                'showFooter' => true,
                'backUrl'    => ShopUtil::getHomeUrl(),
            ])
            ->title('ລາຍການຄຳສັ່ງຊື້ຂອງຂ້ອຍ');
    }

    public function changeTab(string $tab): void
    {
        $this->tab = $tab;

        if ($tab === 'pending') {
            $this->orders = ShopOrder::query()
                ->select(['id', 'created_at'])
                ->where('user_id', '=', auth()->user()->id)
                ->where('payment_status', '=', 'paid')
                ->where('shipping_status', '=', 'pending')
                ->orderByDesc('created_at')
                ->get();
        }
        elseif ($tab === 'shipping') {
            $this->orders = ShopOrder::query()
                ->select(['id', 'created_at'])
                ->where('user_id', '=', auth()->user()->id)
                ->where('payment_status', '=', 'paid')
                ->where('shipping_status', '=', 'shipping')
                ->orderByDesc('created_at')
                ->get();
        }
        elseif ($tab === 'completed') {
            $this->orders = ShopOrder::query()
                ->select(['id', 'created_at'])
                ->where('user_id', '=', auth()->user()->id)
                ->where('payment_status', '=', 'paid')
                ->where('shipping_status', '=', 'completed')
                ->orderByDesc('created_at')
                ->get();
        }
        else {
            $this->orders = ShopOrder::query()
                ->select(['id', 'created_at'])
                ->where('user_id', '=', auth()->user()->id)
                ->where('payment_status', '=', 'paid')
                ->orderByDesc('created_at')
                ->get();
        }
    }
}

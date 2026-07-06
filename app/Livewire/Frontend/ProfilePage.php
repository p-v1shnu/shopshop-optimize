<?php

namespace App\Livewire\Frontend;

use App\Models\ShopOrder;
use Livewire\Attributes\Locked;
use Livewire\Component;

class ProfilePage extends Component
{
    #[Locked]
    public int $totalPendingOrders = 0;

    #[Locked]
    public int $totalShippingOrders = 0;

    #[Locked]
    public int $totalCompletedOrders = 0;

    public function mount(): void
    {
        $orders = ShopOrder::query()
            ->select(['id', 'shipping_status'])
            ->where('user_id', '=', auth()->user()->id)
            ->where('payment_status', '=', 'paid')
            ->orderByDesc('created_at')
            ->get();

        ray($orders);

        $this->totalPendingOrders = $orders->where('shipping_status', 'pending')->count();
        $this->totalShippingOrders = $orders->where('shipping_status', 'shipping')->count();
        $this->totalCompletedOrders = $orders->where('shipping_status', 'completed')->count();
    }

    public function render()
    {
        return view('frontend.livewire.profile-page')
            ->layout('frontend.livewire.layout', [
                'showNavbar' => true,
                'showFooter' => true,
            ])
            ->title('Profile');
    }

    function goToShopPage(): void
    {
        $this->redirect(route('shop.home'));
    }
}

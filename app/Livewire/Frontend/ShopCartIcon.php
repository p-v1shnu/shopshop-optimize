<?php

namespace App\Livewire\Frontend;

use App\Utils\ShopUtil;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

class ShopCartIcon extends Component
{
    #[Locked]
    public int $cartQuantity = 0;

    public function mount(): void
    {
        $this->updateCart();
    }

    public function render()
    {
        return view('frontend.livewire.shop-cart-icon');
    }

    #[On('shop.refreshCart')]
    public function updateCart(): void
    {
        $this->cartQuantity = ShopUtil::getCartQuantity();
    }
}

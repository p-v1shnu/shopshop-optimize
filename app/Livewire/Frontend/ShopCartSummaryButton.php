<?php

namespace App\Livewire\Frontend;

use App\Livewire\Frontend\Concerns\ShopCartTrait;
use Livewire\Component;

class ShopCartSummaryButton extends Component
{
    use ShopCartTrait;

    public function goToCart(): void
    {
        if (auth()->guest()) {
            $this->dispatch('openModal', 'frontend.otp-login-modal', [
                'backUrl' => request()->header('Referer', '/'),
            ]);
            return;
        }

        $this->redirect(route('shop.cart'));
    }

    public function render()
    {
        return view('frontend.livewire.shop-cart-summary-button');
    }
}

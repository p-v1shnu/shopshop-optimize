<?php

namespace App\Livewire\Frontend;

use App\Models\ShopProduct;
use Livewire\Attributes\Locked;
use Livewire\Component;

class ShopCartProduct extends Component
{
    #[Locked]
    public array $cartProduct;

    #[Locked]
    public bool $readOnly;

    public function mount(array $cartProduct, bool $readOnly): void
    {
        $this->cartProduct = $cartProduct;
        $this->readOnly = $readOnly;
    }

    public function render()
    {
        return view('frontend.livewire.shop-cart-product');
    }

    public function removeFromCart(ShopProduct $product): void
    {
        $this->dispatch('shop.removeFromCart', product: $product)->to(ShopCartPage::class);
    }

    public function decreaseQuantity(ShopProduct $product): void
    {
        if ($this->cartProduct['quantity'] <= 1) {
            return;
        }
        $this->dispatch('shop.decreaseQuantity', product: $product)->to(ShopCartPage::class);
    }

    public function increaseQuantity(ShopProduct $product): void
    {
        $this->dispatch('shop.increaseQuantity', product: $product)->to(ShopCartPage::class);
    }
}

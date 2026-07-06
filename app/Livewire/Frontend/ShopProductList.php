<?php

namespace App\Livewire\Frontend;

use App\Exceptions\ShopValidationException;
use App\Models\ShopProduct;
use App\Utils\ShopUtil;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Locked;
use Livewire\Component;

class ShopProductList extends Component
{
    /* @var Collection<ShopProduct> $products */
    #[Locked]
    public Collection $products;

    public function mount($products): void
    {
        $this->products = $products;
    }

    public function render()
    {
        return view('frontend.livewire.shop-product-list');
    }

    public function addToCart(ShopProduct $product, int $quantity): void
    {
        if (auth()->guest()) {
            $this->dispatch('openModal', 'frontend.otp-login-modal', [
                'backUrl' => request()->header('Referer', '/'),
            ]);
            return;
        }

        try {

            ShopUtil::shopValidation();
            ShopUtil::addToCart($product, $quantity);
            $this->dispatch('shop.refreshCart');

        } catch (ShopValidationException $e) {

            $this->dispatch('openModal', 'alert-modal', [
                'type'    => 'info',
                'message' => $e->getMessage(),
            ]);

        }

    }
}

<?php

namespace App\Livewire\Frontend;

use App\Exceptions\ShopValidationException;
use App\Livewire\Frontend\Concerns\ShopCartTrait;
use App\Models\ShopProduct;
use App\Utils\ShopUtil;
use Livewire\Component;

class ShopCartPage extends Component
{
    use ShopCartTrait;

    public function render()
    {
        return view('frontend.livewire.shop-cart-page')
            ->layout('frontend.livewire.layout', [
                'showNavbar' => true,
                'showFooter' => false,
                'backUrl'    => ShopUtil::getHomeUrl(),
            ])
            ->title('ກະຕ່າສິນຄ້າ');
    }

    public function removeFromCart(ShopProduct $product): void
    {
        ShopUtil::removeFromCart($product);
        $this->dispatch('shop.refreshCart');
    }

    public function decreaseQuantity(ShopProduct $product, int $quantity = 1): void
    {
        ShopUtil::decreaseQuantity($product, $quantity);
        $this->dispatch('shop.refreshCart');
    }

    public function increaseQuantity(ShopProduct $product, int $quantity = 1): void
    {
        try {
            ShopUtil::increaseQuantity($product, $quantity);
            $this->dispatch('shop.refreshCart');
        } catch (ShopValidationException $exception) {
            $this->dispatch('openModal', 'alert-modal', [
                'type'    => 'info',
                'message' => $exception->getMessage(),
            ]);
        }
    }

    public function goToShippingPage(): void
    {
        try {
            ShopUtil::shopValidation();
        } catch (ShopValidationException $e) {
            $this->dispatch('openModal', 'alert-modal', [
                'type'    => 'info',
                'message' => $e->getMessage(),
            ]);
            return;
        }

        session()->forget('paymentChannel');

        $this->redirect(route('shop.shipping'));
    }
}

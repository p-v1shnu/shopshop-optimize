<?php

namespace App\Livewire\Frontend;

use App\Exceptions\ShopValidationException;
use App\Models\ShopProduct;
use App\Utils\ShopUtil;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Locked;
use Livewire\Component;

class ShopProductDetailPage extends Component
{
    #[Locked]
    public ShopProduct $product;

    /* @var Collection<ShopProduct> $recommendProducts */
    #[Locked]
    public Collection $recommendProducts;

    public function mount(string $productId): void
    {
        $product = ShopProduct::query()
            ->select(['id', 'name', 'images', 'normal_price', 'price', 'long_description'])
            ->where('id', '=', $productId)
            ->where('status', '=', 'active')
            ->first();

        if (!$product) {
            abort(404);
        }

        $this->product = $product;
        $this->setRecommendProducts();
    }

    public function render()
    {
        return view('frontend.livewire.shop-product-detail-page')
            ->layout('frontend.livewire.layout', [
                'showNavbar' => true,
                'showFooter' => false,
                'backUrl'    => ShopUtil::getHomeUrl(),
            ])
            ->title($this->product->name);
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

    private function setRecommendProducts(): void
    {
        $this->recommendProducts = ShopProduct::query()
            ->select(['id', 'name', 'normal_price', 'price', 'images'])
            ->where('id', '!=', $this->product->id)
            ->where('status', '=', 'active')
            ->inRandomOrder()
            ->take(2)
            ->get();
    }
}

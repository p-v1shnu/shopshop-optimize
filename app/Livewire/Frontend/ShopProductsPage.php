<?php

namespace App\Livewire\Frontend;

use App\Models\ShopProduct;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Locked;
use Livewire\Component;

class ShopProductsPage extends Component
{
    /* @var Collection<ShopProduct> $products */
    #[Locked]
    public Collection $products;

    public function mount(): void
    {
        $this->products = ShopProduct::query()
            ->select(['id', 'name', 'normal_price', 'price', 'images'])
            ->where('status', '=', 'active')
            ->orderBy('sort_no')
            ->get();
    }

    public function render()
    {
        return view('frontend.livewire.shop-products-page')
            ->layout('frontend.livewire.layout', [
                'showNavbar' => true,
                'showFooter' => false,
            ])
            ->title('ຊື້ຜະລິດຕະພັນ ພ້ອມໂປຣໂມຊັນພິເສດ');
    }
}

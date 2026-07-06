<?php

namespace App\Livewire\Frontend;

use App\Utils\FormUtil;
use Livewire\Attributes\Locked;
use Livewire\Component;

class SellerAddressSelector extends Component
{
    public ?string $provinceCode = null;

    public ?string $district = null;

    public ?string $village = null;

    #[Locked]
    public array $districts = [];

    public function mount(): void
    {
        // Restore seller address when back to this page
        $sellerShippingAddress = session('sellerShippingAddress', []);
        $this->provinceCode = $sellerShippingAddress['provinceCode'] ?? null;
        $this->district = $sellerShippingAddress['district'] ?? null;
        $this->village = $sellerShippingAddress['village'] ?? null;

        if ($this->provinceCode) {
            $this->districts = FormUtil::getDistricts($this->provinceCode);
        }
    }

    public function render()
    {
        return view('frontend.livewire.seller-address-selector');
    }

    public function handleProvinceChange(): void
    {
        $this->district = null;
        $this->village = null;
        $this->districts = FormUtil::getDistricts($this->provinceCode);

        $this->dispatchAddressEvent();
    }

    public function updatedDistrict(): void
    {
        $this->dispatchAddressEvent();
    }

    public function updatedVillage(): void
    {
        $this->dispatchAddressEvent();
    }

    private function dispatchAddressEvent(): void
    {
        $this->dispatch('sellerShippingAddressSelected',
            provinceCode: $this->provinceCode ?? '',
            district: $this->district ?? '',
            village: $this->village ?? '',
        );
    }
}

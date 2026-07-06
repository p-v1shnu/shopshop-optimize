<?php

namespace App\Livewire\Frontend;

use Livewire\Attributes\Locked;
use LivewireUI\Modal\ModalComponent;

class PopupBannerModal extends ModalComponent
{
    #[Locked]
    public array $images = [];

    public function render()
    {
        return view('frontend.livewire.popup-banner-modal');
    }
}

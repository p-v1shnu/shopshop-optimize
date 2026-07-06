<?php

namespace App\Livewire\Frontend;

use LivewireUI\Modal\ModalComponent;

class ShopNoticeModal extends ModalComponent
{
    public static function closeModalOnEscape(): bool
    {
        return false;
    }

    public static function closeModalOnClickAway(): bool
    {
        return false;
    }

    public function render()
    {
        return view('frontend.livewire.shop-notice-modal');
    }
}

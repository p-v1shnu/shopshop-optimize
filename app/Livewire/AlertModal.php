<?php

namespace App\Livewire;

use Livewire\Attributes\Locked;
use LivewireUI\Modal\ModalComponent;

class AlertModal extends ModalComponent
{
    #[Locked]
    public string $type;

    #[Locked]
    public string $message;

    #[Locked]
    public string $description;

    #[Locked]
    public ?string $buttonLink = null;

    #[Locked]
    public string $buttonText = 'ປິດ';

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
        return view('frontend.livewire.alert-modal');
    }
}

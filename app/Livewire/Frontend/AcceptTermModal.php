<?php

namespace App\Livewire\Frontend;

use LivewireUI\Modal\ModalComponent;

class AcceptTermModal extends ModalComponent
{
    public $isAccept = false;

    public function mount(bool $isAccept): void
    {
        $this->isAccept = $isAccept;
    }

    public function render()
    {
        return view('frontend.livewire.accept-term-modal');
    }

    public function handleAcceptTerm(): void
    {
        $this->dispatch('acceptTerm', ['isAccept' => true])->to(ProfileEditPage::class);
        $this->dispatch('closeModal');
    }
}

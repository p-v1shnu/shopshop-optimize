<?php

namespace App\Livewire\Frontend;

use Livewire\Attributes\Locked;
use Livewire\Component;

class PopupBanner extends Component
{
    #[Locked]
    public $show = false;

    #[Locked]
    public array $images = [];

    public function mount(): void
    {
        // TODO: add image here to show in banner, or other logic to determine if the banner should be shown
        $this->images = tenant('popup_banners') ?? [];

        if (count($this->images) === 0) {
            $this->show = false;
            return;
        }

        $user = auth()->user();

        // Show every 10 minutes
        $expire = now()->addMinutes(10);

        if ($user !== null) {
            // For logged-in users: use user-specific cache
            $cacheTags = ['user_' . $user->id];
            $cacheKey = 'show_shop_promotion_banner_popup';

            if (cache()->tags($cacheTags)->has($cacheKey) === false) {
                cache()->tags($cacheTags)->put($cacheKey, true, $expire);
                $this->show = true;
            }
        } else {
            // For guest users: use session-based cache
            $sessionId = session()->getId();
            $cacheKey = 'show_shop_promotion_banner_popup_guest_' . $sessionId;

            if (cache()->has($cacheKey) === false) {
                cache()->put($cacheKey, true, $expire);
                $this->show = true;
            }
        }
    }

    public function render()
    {
        return view('frontend.livewire.popup-banner');
    }
}

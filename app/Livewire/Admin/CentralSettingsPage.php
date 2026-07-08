<?php

namespace App\Livewire\Admin;

use App\Models\Setting;
use Livewire\Component;

class CentralSettingsPage extends Component
{
    public ?string $title = null;

    public ?string $facebookCoverUrl = null;

    public ?string $landingPageUrl = null;

    public function mount(): void
    {
        $this->fillFromSetting($this->setting());
    }

    public function save(): void
    {
        $validated = $this->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'facebookCoverUrl' => ['nullable', 'url', 'max:255'],
            'landingPageUrl' => ['nullable', 'url', 'max:255'],
        ]);

        $setting = $this->setting();

        $setting->fill([
            'title' => $this->blankToNull($validated['title']),
            'facebook_cover_url' => $this->blankToNull($validated['facebookCoverUrl']),
            'landing_page_url' => $this->blankToNull($validated['landingPageUrl']),
        ]);

        $setting->save();

        $this->fillFromSetting($setting->refresh());
    }

    public function render()
    {
        return view('admin.central-settings-page')
            ->layout('admin.layout')
            ->title('Central settings');
    }

    private function setting(): Setting
    {
        return app(Setting::class);
    }

    private function fillFromSetting(Setting $setting): void
    {
        $this->title = $setting->title;
        $this->facebookCoverUrl = $setting->facebook_cover_url;
        $this->landingPageUrl = $setting->landing_page_url;
    }

    private function blankToNull(?string $value): ?string
    {
        return blank($value) ? null : $value;
    }
}

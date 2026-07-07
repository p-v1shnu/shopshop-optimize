<?php

namespace App\Livewire\Admin;

use App\Models\Tenant;
use App\Support\AdminTenantScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\Features\SupportFileUploads\WithFileUploads;

class BannersPage extends Component
{
    use WithFileUploads;

    public string $tenantId;

    public array $homepageBanners = [];

    public array $popupBanners = [];

    public array $homepageUploads = [];

    public array $popupUploads = [];

    public function mount(AdminTenantScope $tenantScope): void
    {
        $admin = Auth::guard('admin')->user();
        $this->tenantId = $admin->isShop()
            ? $admin->tenant_id
            : (string) $tenantScope->currentTenantId();

        abort_if(blank($this->tenantId), 404);

        $this->fillFromTenant($this->currentTenant());
    }

    public function addHomepageBanners(): void
    {
        $this->addBanners('homepageUploads', 'homepageBanners', 'homepage_banners', 'homepage');
    }

    public function addPopupBanners(): void
    {
        $this->addBanners('popupUploads', 'popupBanners', 'popup_banners', 'popup');
    }

    public function moveHomepageBanner(int $index, string $direction): void
    {
        $this->moveBanner('homepageBanners', 'homepage_banners', $index, $direction);
    }

    public function movePopupBanner(int $index, string $direction): void
    {
        $this->moveBanner('popupBanners', 'popup_banners', $index, $direction);
    }

    public function removeHomepageBanner(int $index): void
    {
        $this->removeBanner('homepageBanners', 'homepage_banners', $index);
    }

    public function removePopupBanner(int $index): void
    {
        $this->removeBanner('popupBanners', 'popup_banners', $index);
    }

    public function render()
    {
        return view('admin.banners-page', [
            'tenant' => $this->currentTenant(),
        ])->layout('admin.layout')
            ->title('Banners');
    }

    private function addBanners(string $uploadProperty, string $bannerProperty, string $tenantColumn, string $folder): void
    {
        $this->validate([
            "{$uploadProperty}.*" => ['image', 'max:4096'],
        ]);

        $banners = collect($this->{$bannerProperty});

        foreach ($this->{$uploadProperty} as $upload) {
            if (! $upload instanceof TemporaryUploadedFile) {
                continue;
            }

            $path = $upload->storePubliclyAs(
                "banners/{$this->tenantId}/{$folder}",
                Str::uuid().'.'.$upload->getClientOriginalExtension()
            );

            $banners->push(Storage::disk(config('filesystems.default'))->url($path));
        }

        $this->{$bannerProperty} = $banners->values()->all();
        $this->{$uploadProperty} = [];
        $this->persist($tenantColumn, $this->{$bannerProperty});
        $this->resetValidation($uploadProperty);
    }

    private function moveBanner(string $bannerProperty, string $tenantColumn, int $index, string $direction): void
    {
        $banners = array_values($this->{$bannerProperty});
        $targetIndex = $direction === 'up' ? $index - 1 : $index + 1;

        if (! isset($banners[$index]) || ! isset($banners[$targetIndex])) {
            return;
        }

        [$banners[$index], $banners[$targetIndex]] = [$banners[$targetIndex], $banners[$index]];

        $this->{$bannerProperty} = array_values($banners);
        $this->persist($tenantColumn, $this->{$bannerProperty});
    }

    private function removeBanner(string $bannerProperty, string $tenantColumn, int $index): void
    {
        $banners = array_values($this->{$bannerProperty});

        if (! isset($banners[$index])) {
            return;
        }

        array_splice($banners, $index, 1);

        $this->{$bannerProperty} = array_values($banners);
        $this->persist($tenantColumn, $this->{$bannerProperty});
    }

    private function persist(string $tenantColumn, array $banners): void
    {
        $this->currentTenant()->update([
            $tenantColumn => array_values($banners),
        ]);
    }

    private function currentTenant(): Tenant
    {
        return Tenant::query()->findOrFail($this->tenantId);
    }

    private function fillFromTenant(Tenant $tenant): void
    {
        $this->homepageBanners = $this->normalizeBanners($tenant->homepage_banners);
        $this->popupBanners = $this->normalizeBanners($tenant->popup_banners);
    }

    private function normalizeBanners(mixed $banners): array
    {
        return collect($banners ?? [])
            ->filter(fn (mixed $banner): bool => is_string($banner) && filled($banner))
            ->values()
            ->all();
    }
}

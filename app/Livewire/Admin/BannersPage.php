<?php

namespace App\Livewire\Admin;

use App\Models\Tenant;
use App\Support\AdminActivityLogger;
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
        $this->addBanners('homepageUploads', 'homepageBanners', 'homepage_banners', 'homepage', 'banners.homepage.added');
    }

    public function addPopupBanners(): void
    {
        $this->addBanners('popupUploads', 'popupBanners', 'popup_banners', 'popup', 'banners.popup.added');
    }

    public function moveHomepageBanner(int $index, string $direction): void
    {
        $this->moveBanner('homepageBanners', 'homepage_banners', $index, $direction, 'banners.homepage.moved');
    }

    public function movePopupBanner(int $index, string $direction): void
    {
        $this->moveBanner('popupBanners', 'popup_banners', $index, $direction, 'banners.popup.moved');
    }

    public function removeHomepageBanner(int $index): void
    {
        $this->removeBanner('homepageBanners', 'homepage_banners', $index, 'banners.homepage.removed');
    }

    public function removePopupBanner(int $index): void
    {
        $this->removeBanner('popupBanners', 'popup_banners', $index, 'banners.popup.removed');
    }

    public function render()
    {
        return view('admin.banners-page', [
            'tenant' => $this->currentTenant(),
        ])->layout('admin.layout')
            ->title('Banners');
    }

    private function addBanners(string $uploadProperty, string $bannerProperty, string $tenantColumn, string $folder, string $action): void
    {
        $this->validate([
            "{$uploadProperty}.*" => ['image', 'max:4096'],
        ]);

        $banners = collect($this->{$bannerProperty});
        $addedCount = 0;

        foreach ($this->{$uploadProperty} as $upload) {
            if (! $upload instanceof TemporaryUploadedFile) {
                continue;
            }

            $path = $upload->storePubliclyAs(
                "banners/{$this->tenantId}/{$folder}",
                Str::uuid().'.'.$upload->getClientOriginalExtension()
            );

            $banners->push(Storage::disk(config('filesystems.default'))->url($path));
            $addedCount++;
        }

        $this->{$bannerProperty} = $banners->values()->all();
        $this->{$uploadProperty} = [];
        $this->persist($tenantColumn, $this->{$bannerProperty});
        $this->logBannerChange($action, $tenantColumn, [
            'added_count' => $addedCount,
            'total_count' => count($this->{$bannerProperty}),
        ]);
        $this->resetValidation($uploadProperty);
    }

    private function moveBanner(string $bannerProperty, string $tenantColumn, int $index, string $direction, string $action): void
    {
        $banners = array_values($this->{$bannerProperty});
        $targetIndex = $direction === 'up' ? $index - 1 : $index + 1;

        if (! isset($banners[$index]) || ! isset($banners[$targetIndex])) {
            return;
        }

        [$banners[$index], $banners[$targetIndex]] = [$banners[$targetIndex], $banners[$index]];

        $this->{$bannerProperty} = array_values($banners);
        $this->persist($tenantColumn, $this->{$bannerProperty});
        $this->logBannerChange($action, $tenantColumn, [
            'from_index' => $index,
            'to_index' => $targetIndex,
            'direction' => $direction,
        ]);
    }

    private function removeBanner(string $bannerProperty, string $tenantColumn, int $index, string $action): void
    {
        $banners = array_values($this->{$bannerProperty});

        if (! isset($banners[$index])) {
            return;
        }

        array_splice($banners, $index, 1);

        $this->{$bannerProperty} = array_values($banners);
        $this->persist($tenantColumn, $this->{$bannerProperty});
        $this->logBannerChange($action, $tenantColumn, [
            'removed_index' => $index,
            'total_count' => count($this->{$bannerProperty}),
        ]);
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

    private function logBannerChange(string $action, string $tenantColumn, array $detail): void
    {
        app(AdminActivityLogger::class)->log($action, $this->currentTenant(), array_merge([
            'tenant_id' => $this->tenantId,
            'column' => $tenantColumn,
        ], $detail));
    }

    private function normalizeBanners(mixed $banners): array
    {
        return collect($banners ?? [])
            ->filter(fn (mixed $banner): bool => is_string($banner) && filled($banner))
            ->values()
            ->all();
    }
}

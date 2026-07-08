<?php

namespace App\Livewire\Admin;

use App\Models\Tenant;
use App\Support\AdminActivityLogger;
use App\Support\AdminTenantScope;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class SettingsPage extends Component
{
    public string $tenantId;

    public bool $enableShop = true;

    public bool $enableCoupon = false;

    public ?string $shopClosedAt = null;

    public ?string $campaignCode = null;

    public ?string $campaignStartsAt = null;

    public ?string $campaignEndsAt = null;

    public function mount(AdminTenantScope $tenantScope): void
    {
        $admin = Auth::guard('admin')->user();
        $this->tenantId = $admin->isShop()
            ? $admin->tenant_id
            : (string) $tenantScope->currentTenantId();

        abort_if(blank($this->tenantId), 404);

        $this->fillFromTenant($this->currentTenant());
    }

    public function save(): void
    {
        $validated = $this->validate([
            'enableShop' => ['boolean'],
            'enableCoupon' => ['boolean'],
            'shopClosedAt' => ['nullable', 'date'],
            'campaignCode' => ['nullable', 'string', 'max:255'],
            'campaignStartsAt' => ['nullable', 'date', 'required_with:campaignCode,campaignEndsAt'],
            'campaignEndsAt' => ['nullable', 'date', 'required_with:campaignCode,campaignStartsAt', 'after_or_equal:campaignStartsAt'],
        ]);

        $tenant = $this->currentTenant();

        $tenant->update([
            'enable_shop' => $validated['enableShop'],
            'enable_coupon' => $validated['enableCoupon'],
            'shop_closed_at' => $this->normalizeDateTime($validated['shopClosedAt']),
            'campaign_code' => $this->normalizeBlank($validated['campaignCode']),
            'campaign_starts_at' => $this->normalizeDateTime($validated['campaignStartsAt']),
            'campaign_ends_at' => $this->normalizeDateTime($validated['campaignEndsAt']),
        ]);

        app(AdminActivityLogger::class)->log('settings.saved', $tenant, [
            'tenant_id' => $tenant->id,
            'enable_shop' => (bool) $validated['enableShop'],
            'enable_coupon' => (bool) $validated['enableCoupon'],
            'campaign_code' => $this->normalizeBlank($validated['campaignCode']),
        ]);

        $this->fillFromTenant($tenant->refresh());
    }

    public function render()
    {
        return view('admin.settings-page', [
            'tenant' => $this->currentTenant(),
        ])->layout('admin.layout')
            ->title('Settings');
    }

    private function currentTenant(): Tenant
    {
        return Tenant::query()->findOrFail($this->tenantId);
    }

    private function fillFromTenant(Tenant $tenant): void
    {
        $this->enableShop = (bool) $tenant->enable_shop;
        $this->enableCoupon = (bool) $tenant->enable_coupon;
        $this->shopClosedAt = $this->formatForInput($tenant->shop_closed_at);
        $this->campaignCode = $tenant->campaign_code;
        $this->campaignStartsAt = $this->formatForInput($tenant->campaign_starts_at);
        $this->campaignEndsAt = $this->formatForInput($tenant->campaign_ends_at);
    }

    private function formatForInput(mixed $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        if ($value instanceof CarbonInterface) {
            return $value->format('Y-m-d\TH:i');
        }

        return $value;
    }

    private function normalizeDateTime(?string $value): ?string
    {
        return blank($value)
            ? null
            : str_replace('T', ' ', $value).(strlen($value) === 16 ? ':00' : '');
    }

    private function normalizeBlank(?string $value): ?string
    {
        return blank($value) ? null : $value;
    }
}

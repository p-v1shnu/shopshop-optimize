<?php

namespace App\Livewire\Admin;

use App\Models\Tenant;
use App\Utils\FormUtil;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\Features\SupportFileUploads\WithFileUploads;
use Livewire\WithPagination;
use Stancl\Tenancy\Database\Models\Domain;

class BrandsPage extends Component
{
    use WithFileUploads, WithPagination;

    public string $createId = '';

    public string $createName = '';

    public string $createStatus = 'active';

    public string $createDomain = '';

    public ?string $editingTenantId = null;

    public bool $isEditing = false;

    public string $name = '';

    public string $status = 'active';

    public ?string $siteLogoUrl = null;

    public mixed $siteLogoUpload = null;

    public ?string $facebookName = null;

    public ?string $facebookUrl = null;

    public ?string $facebookCoverUrl = null;

    public ?string $googleTagManagerId = null;

    public ?string $googleAnalyticsId = null;

    public array $shippingChannels = [];

    public array $allowProvinceIds = [];

    public bool $maintenanceMode = false;

    public ?string $orderInvoiceWebhookUrl = null;

    public ?string $supportContactPhone = null;

    public ?string $deliveryContactPhone = null;

    public ?string $noShippingInstructionText = null;

    public ?string $noShippingPaidText = null;

    public ?string $latitude = null;

    public ?string $longitude = null;

    public ?string $otpSiteName = null;

    public ?string $contactUrl = null;

    public ?string $footerMoreInfoText = null;

    public ?string $footerMoreInfoLink = null;

    public ?string $headHtml = null;

    public ?string $title = null;

    public string $newDomain = '';

    public function createTenant(): void
    {
        $validated = $this->validate($this->createRules());

        DB::transaction(function () use ($validated): void {
            $tenant = Tenant::query()->create([
                'id' => $validated['createId'],
                'name' => $validated['createName'],
                'status' => $validated['createStatus'],
                'enable_shop' => true,
                'enable_coupon' => false,
            ]);

            $tenant->domains()->create([
                'domain' => $this->normalizeDomain($validated['createDomain']),
            ]);
        });

        $this->reset(['createId', 'createName', 'createDomain']);
        $this->createStatus = 'active';
    }

    public function edit(string $tenantId): void
    {
        $tenant = $this->findTenant($tenantId);

        $this->editingTenantId = $tenant->id;
        $this->isEditing = true;
        $this->name = (string) $tenant->name;
        $this->status = $tenant->status;
        $this->siteLogoUrl = $tenant->site_logo_url;
        $this->siteLogoUpload = null;
        $this->facebookName = $tenant->facebook_name;
        $this->facebookUrl = $tenant->facebook_url;
        $this->facebookCoverUrl = $tenant->facebook_cover_url;
        $this->googleTagManagerId = $tenant->google_tag_manager_id;
        $this->googleAnalyticsId = $tenant->google_analytics_id;
        $this->shippingChannels = $tenant->shipping_channels ?? [];
        $this->allowProvinceIds = $tenant->allow_province_ids ?? [];
        $this->maintenanceMode = (bool) $tenant->maintenance_mode;
        $this->orderInvoiceWebhookUrl = $tenant->order_invoice_webhook_url;
        $this->supportContactPhone = $tenant->support_contact_phone;
        $this->deliveryContactPhone = implode("\n", $tenant->delivery_contact_phone ?? []);
        $this->noShippingInstructionText = $tenant->no_shipping_instruction_text;
        $this->noShippingPaidText = $tenant->no_shipping_order_paid_text;
        $this->latitude = $tenant->latitude;
        $this->longitude = $tenant->longitude;
        $this->otpSiteName = $tenant->otp_site_name;
        $this->contactUrl = $tenant->contact_url;
        $this->footerMoreInfoText = $tenant->footer_more_info_text;
        $this->footerMoreInfoLink = $tenant->footer_more_info_link;
        $this->headHtml = $tenant->head_html;
        $this->title = $tenant->title;
        $this->newDomain = '';
        $this->resetValidation();
    }

    public function cancelEdit(): void
    {
        $this->resetEditForm();
    }

    public function save(): void
    {
        abort_if(! $this->editingTenantId, 404);

        $validated = $this->validate($this->editRules());
        $siteLogoUrl = $this->siteLogoUrl;

        if ($this->siteLogoUpload instanceof TemporaryUploadedFile) {
            $path = $this->siteLogoUpload->storePubliclyAs(
                "tenants/{$this->editingTenantId}",
                Str::uuid().'.'.$this->siteLogoUpload->getClientOriginalExtension()
            );

            $siteLogoUrl = Storage::disk(config('filesystems.default'))->url($path);
        }

        $this->findTenant($this->editingTenantId)->update([
            'name' => $validated['name'],
            'status' => $validated['status'],
            'site_logo_url' => $siteLogoUrl,
            'facebook_name' => $this->blankToNull($validated['facebookName']),
            'facebook_url' => $this->blankToNull($validated['facebookUrl']),
            'facebook_cover_url' => $this->blankToNull($validated['facebookCoverUrl']),
            'google_tag_manager_id' => $this->blankToNull($validated['googleTagManagerId']),
            'google_analytics_id' => $this->blankToNull($validated['googleAnalyticsId']),
            'shipping_channels' => $validated['shippingChannels'],
            'allow_province_ids' => $validated['allowProvinceIds'],
            'maintenance_mode' => $validated['maintenanceMode'],
            'order_invoice_webhook_url' => $this->blankToNull($validated['orderInvoiceWebhookUrl']),
            'support_contact_phone' => $this->blankToNull($validated['supportContactPhone']),
            'delivery_contact_phone' => $this->normalizePhoneList($validated['deliveryContactPhone']),
            'no_shipping_instruction_text' => $this->blankToNull($validated['noShippingInstructionText']),
            'no_shipping_order_paid_text' => $this->blankToNull($validated['noShippingPaidText']),
            'latitude' => $this->blankToNull($validated['latitude']),
            'longitude' => $this->blankToNull($validated['longitude']),
            'otp_site_name' => $this->blankToNull($validated['otpSiteName']),
            'contact_url' => $this->blankToNull($validated['contactUrl']),
            'footer_more_info_text' => $this->blankToNull($validated['footerMoreInfoText']),
            'footer_more_info_link' => $this->blankToNull($validated['footerMoreInfoLink']),
            'head_html' => $this->blankToNull($validated['headHtml']),
            'title' => $this->blankToNull($validated['title']),
        ]);

        $this->edit($this->editingTenantId);
    }

    public function addDomain(): void
    {
        abort_if(! $this->editingTenantId, 404);

        $validated = $this->validate([
            'newDomain' => $this->domainRules(),
        ]);

        $this->findTenant($this->editingTenantId)->domains()->create([
            'domain' => $this->normalizeDomain($validated['newDomain']),
        ]);

        $this->newDomain = '';
    }

    public function removeDomain(int $domainId): void
    {
        abort_if(! $this->editingTenantId, 404);

        $domain = Domain::query()
            ->where('tenant_id', $this->editingTenantId)
            ->whereKey($domainId)
            ->first();

        abort_if(! $domain, 404);

        $domain->delete();
    }

    public function render()
    {
        return view('admin.brands-page', [
            'tenants' => Tenant::query()
                ->with('domains')
                ->orderBy('name')
                ->paginate(10),
            'selectedTenant' => $this->selectedTenant(),
            'provinces' => FormUtil::getProvinces(),
        ])->layout('admin.layout')
            ->title('Brands');
    }

    private function createRules(): array
    {
        return [
            'createId' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('tenants', 'id')],
            'createName' => ['required', 'string', 'max:255'],
            'createStatus' => ['required', Rule::in(['active', 'inactive'])],
            'createDomain' => $this->domainRules(),
        ];
    }

    private function editRules(): array
    {
        $provinceIds = collect(FormUtil::getProvinces())->pluck('id')->all();

        return [
            'name' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'siteLogoUpload' => ['nullable', 'image', 'max:4096'],
            'facebookName' => ['nullable', 'string', 'max:255'],
            'facebookUrl' => ['nullable', 'url', 'max:255'],
            'facebookCoverUrl' => ['nullable', 'url', 'max:255'],
            'googleTagManagerId' => ['nullable', 'string', 'max:255'],
            'googleAnalyticsId' => ['nullable', 'string', 'max:255'],
            'shippingChannels' => ['array'],
            'shippingChannels.*' => [Rule::in(['hal', 'seller', 'none'])],
            'allowProvinceIds' => ['array'],
            'allowProvinceIds.*' => [Rule::in($provinceIds)],
            'maintenanceMode' => ['boolean'],
            'orderInvoiceWebhookUrl' => ['nullable', 'url', 'max:255'],
            'supportContactPhone' => ['nullable', 'string', 'max:255'],
            'deliveryContactPhone' => ['nullable', 'string'],
            'noShippingInstructionText' => ['nullable', 'string'],
            'noShippingPaidText' => ['nullable', 'string'],
            'latitude' => ['nullable', 'string', 'max:255'],
            'longitude' => ['nullable', 'string', 'max:255'],
            'otpSiteName' => ['nullable', 'string', 'max:255'],
            'contactUrl' => ['nullable', 'url', 'max:255'],
            'footerMoreInfoText' => ['nullable', 'string', 'max:255'],
            'footerMoreInfoLink' => ['nullable', 'url', 'max:255'],
            'headHtml' => ['nullable', 'string'],
            'title' => ['nullable', 'string', 'max:255'],
        ];
    }

    private function domainRules(): array
    {
        return ['required', 'string', 'max:255', 'regex:/^[a-z0-9.-]+$/', Rule::unique('domains', 'domain')];
    }

    private function selectedTenant(): ?Tenant
    {
        if (! $this->editingTenantId) {
            return null;
        }

        return $this->findTenant($this->editingTenantId)->load('domains');
    }

    private function findTenant(string $tenantId): Tenant
    {
        return Tenant::query()->findOrFail($tenantId);
    }

    private function resetEditForm(): void
    {
        $this->reset([
            'editingTenantId',
            'isEditing',
            'name',
            'siteLogoUrl',
            'siteLogoUpload',
            'facebookName',
            'facebookUrl',
            'facebookCoverUrl',
            'googleTagManagerId',
            'googleAnalyticsId',
            'shippingChannels',
            'allowProvinceIds',
            'orderInvoiceWebhookUrl',
            'supportContactPhone',
            'deliveryContactPhone',
            'noShippingInstructionText',
            'noShippingPaidText',
            'latitude',
            'longitude',
            'otpSiteName',
            'contactUrl',
            'footerMoreInfoText',
            'footerMoreInfoLink',
            'headHtml',
            'title',
            'newDomain',
        ]);

        $this->status = 'active';
        $this->maintenanceMode = false;
        $this->resetValidation();
    }

    private function normalizeDomain(string $domain): string
    {
        return strtolower(trim($domain));
    }

    private function normalizePhoneList(?string $value): array
    {
        if (blank($value)) {
            return [];
        }

        return collect(preg_split('/[\r\n,]+/', $value))
            ->map(fn (string $phone): string => trim($phone))
            ->filter()
            ->values()
            ->all();
    }

    private function blankToNull(mixed $value): mixed
    {
        return blank($value) ? null : $value;
    }
}

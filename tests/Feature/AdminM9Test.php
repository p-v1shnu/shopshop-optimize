<?php

namespace Tests\Feature;

use App\Livewire\Admin\BrandsPage;
use App\Models\Admin;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Stancl\Tenancy\Database\Models\Domain;
use Tests\TestCase;

class AdminM9Test extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        tenancy()->end();

        parent::tearDown();
    }

    public function test_super_admin_can_list_create_tenant_and_domain_without_database_provisioning(): void
    {
        $this->createTenant('babybright', 'Baby Bright', 'babybright.shopshop.test');
        $super = $this->createSuperAdmin();

        $this->actingAs($super, 'admin');

        Livewire::test(BrandsPage::class)
            ->assertSee('Baby Bright')
            ->assertSee('babybright')
            ->assertSee('babybright.shopshop.test')
            ->set('createId', 'new-brand')
            ->set('createName', 'New Brand')
            ->set('createStatus', 'active')
            ->set('createDomain', 'new-brand.shopshop.test')
            ->call('createTenant')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('tenants', [
            'id' => 'new-brand',
            'name' => 'New Brand',
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('domains', [
            'tenant_id' => 'new-brand',
            'domain' => 'new-brand.shopshop.test',
        ]);
        $this->assertTrue(Tenant::query()->whereKey('new-brand')->exists());
        $this->assertTrue(Domain::query()->where('domain', 'new-brand.shopshop.test')->exists());
    }

    public function test_tenant_domain_resolves_storefront_after_creation(): void
    {
        $this->createTenant('babybright', 'Baby Bright', 'babybright.shopshop.test');
        $super = $this->createSuperAdmin();

        $this->actingAs($super, 'admin');

        Livewire::test(BrandsPage::class)
            ->set('createId', 'resolve-brand')
            ->set('createName', 'Resolve Brand')
            ->set('createStatus', 'active')
            ->set('createDomain', 'resolve-brand.shopshop.test')
            ->call('createTenant')
            ->assertHasNoErrors();

        $this->get('http://resolve-brand.shopshop.test/shop/search')
            ->assertOk();

        $this->assertSame('resolve-brand', tenant('id'));
    }

    public function test_super_admin_can_edit_config_fields_with_logo_upload_and_array_fields(): void
    {
        Storage::fake('s3');
        config(['filesystems.default' => 's3']);

        $tenant = $this->createTenant('babybright', 'Baby Bright', 'babybright.shopshop.test');
        $super = $this->createSuperAdmin();

        $this->actingAs($super, 'admin');

        Livewire::test(BrandsPage::class)
            ->call('edit', $tenant->id)
            ->set('name', 'Baby Bright Updated')
            ->set('status', 'inactive')
            ->set('siteLogoUpload', UploadedFile::fake()->image('logo.png', 256, 128))
            ->set('facebookName', 'Baby FB')
            ->set('facebookUrl', 'https://facebook.example/baby')
            ->set('facebookCoverUrl', 'https://assets.example/cover.jpg')
            ->set('googleTagManagerId', 'GTM-ABC123')
            ->set('googleAnalyticsId', 'G-ABC123')
            ->set('shippingChannels', ['hal', 'seller', 'none'])
            ->set('allowProvinceIds', ['VT', 'CH'])
            ->set('maintenanceMode', true)
            ->set('orderInvoiceWebhookUrl', 'https://invoice.example.test/orders')
            ->set('supportContactPhone', '+8562088551216')
            ->set('deliveryContactPhone', "+8562095337188\n+8562055575787")
            ->set('noShippingInstructionText', 'Pick up at the store.')
            ->set('noShippingPaidText', 'Bring your paid order.')
            ->set('latitude', '17.962166')
            ->set('longitude', '102.6256462')
            ->set('otpSiteName', 'Shop Shop - Baby Updated')
            ->set('contactUrl', 'https://contact.example.test')
            ->set('footerMoreInfoText', 'More products')
            ->set('footerMoreInfoLink', 'https://more.example.test')
            ->set('headHtml', '<meta name="x-test" content="ok">')
            ->set('title', 'Updated Store Title')
            ->call('save')
            ->assertHasNoErrors();

        $tenant->refresh();

        $this->assertSame('Baby Bright Updated', $tenant->name);
        $this->assertSame('inactive', $tenant->status);
        $this->assertStringContainsString('/tenants/babybright/', $tenant->site_logo_url);
        $this->assertSame('Baby FB', $tenant->facebook_name);
        $this->assertSame('https://facebook.example/baby', $tenant->facebook_url);
        $this->assertSame('https://assets.example/cover.jpg', $tenant->facebook_cover_url);
        $this->assertSame('GTM-ABC123', $tenant->google_tag_manager_id);
        $this->assertSame('G-ABC123', $tenant->google_analytics_id);
        $this->assertSame(['hal', 'seller', 'none'], $tenant->shipping_channels);
        $this->assertSame(['VT', 'CH'], $tenant->allow_province_ids);
        $this->assertTrue((bool) $tenant->maintenance_mode);
        $this->assertSame('https://invoice.example.test/orders', $tenant->order_invoice_webhook_url);
        $this->assertSame('+8562088551216', $tenant->support_contact_phone);
        $this->assertSame(['+8562095337188', '+8562055575787'], $tenant->delivery_contact_phone);
        $this->assertSame('Pick up at the store.', $tenant->no_shipping_instruction_text);
        $this->assertSame('Bring your paid order.', $tenant->no_shipping_order_paid_text);
        $this->assertSame('17.962166', $tenant->latitude);
        $this->assertSame('102.6256462', $tenant->longitude);
        $this->assertSame('Shop Shop - Baby Updated', $tenant->otp_site_name);
        $this->assertSame('https://contact.example.test', $tenant->contact_url);
        $this->assertSame('More products', $tenant->footer_more_info_text);
        $this->assertSame('https://more.example.test', $tenant->footer_more_info_link);
        $this->assertSame('<meta name="x-test" content="ok">', $tenant->head_html);
        $this->assertSame('Updated Store Title', $tenant->title);

        $storedPath = ltrim(str_replace('/storage/', '', parse_url($tenant->site_logo_url, PHP_URL_PATH)), '/');
        Storage::disk('s3')->assertExists($storedPath);
    }

    public function test_super_admin_can_add_and_remove_domains(): void
    {
        $tenant = $this->createTenant('babybright', 'Baby Bright', 'babybright.shopshop.test');
        $super = $this->createSuperAdmin();

        $this->actingAs($super, 'admin');

        Livewire::test(BrandsPage::class)
            ->call('edit', $tenant->id)
            ->set('newDomain', 'babybright-secondary.shopshop.test')
            ->call('addDomain')
            ->assertHasNoErrors();

        $domain = Domain::query()
            ->where('tenant_id', 'babybright')
            ->where('domain', 'babybright-secondary.shopshop.test')
            ->firstOrFail();

        Livewire::test(BrandsPage::class)
            ->call('edit', $tenant->id)
            ->call('removeDomain', $domain->id)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('domains', [
            'id' => $domain->id,
            'domain' => 'babybright-secondary.shopshop.test',
        ]);
        $this->assertDatabaseHas('domains', [
            'tenant_id' => 'babybright',
            'domain' => 'babybright.shopshop.test',
        ]);
    }

    public function test_shop_admin_cannot_access_brand_management_routes_or_nav(): void
    {
        $this->createTenant('babybright', 'Baby Bright', 'babybright.shopshop.test');
        $shopAdmin = $this->createShopAdmin('babybright');

        $this->actingAs($shopAdmin, 'admin');

        $this->get('http://admin.shopshop.test/admin')
            ->assertOk()
            ->assertDontSee('Brands');

        $this->get('http://admin.shopshop.test/admin/brands')
            ->assertForbidden();
    }

    private function createTenant(string $id, string $name, string $domain): Tenant
    {
        $tenant = Tenant::query()->create([
            'id' => $id,
            'name' => $name,
            'status' => 'active',
            'enable_shop' => true,
            'enable_coupon' => false,
        ]);

        $tenant->domains()->create(['domain' => $domain]);

        return $tenant;
    }

    private function createSuperAdmin(): Admin
    {
        return Admin::query()->create([
            'name' => 'Super Admin',
            'email' => 'super@example.com',
            'password' => Hash::make('password'),
            'role' => 'super',
            'tenant_id' => null,
            'status' => 'active',
        ]);
    }

    private function createShopAdmin(string $tenantId): Admin
    {
        return Admin::query()->create([
            'name' => 'Shop Admin',
            'email' => 'shop-'.$tenantId.'@example.com',
            'password' => Hash::make('password'),
            'role' => 'shop',
            'tenant_id' => $tenantId,
            'status' => 'active',
        ]);
    }
}

<?php

namespace Tests\Feature;

use App\Livewire\Admin\SettingsPage;
use App\Models\Admin;
use App\Models\Tenant;
use App\Utils\ShopUtil;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class AdminM2Test extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        tenancy()->end();
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_saving_settings_persists_columns_and_virtual_tenant_data(): void
    {
        $this->createTenant('babybright', 'Baby Bright');
        $super = $this->createSuperAdmin();

        $this->actingAs($super, 'admin');
        $this->withSession(['admin_current_tenant_id' => 'babybright']);

        Livewire::test(SettingsPage::class)
            ->set('enableShop', false)
            ->set('enableCoupon', true)
            ->set('shopClosedAt', '2026-08-01T18:30')
            ->set('campaignCode', 'august_sale')
            ->set('campaignStartsAt', '2026-08-01T00:00')
            ->set('campaignEndsAt', '2026-08-07T23:59')
            ->call('save')
            ->assertHasNoErrors();

        $tenant = Tenant::query()->findOrFail('babybright');

        $this->assertFalse($tenant->enable_shop);
        $this->assertTrue($tenant->enable_coupon);
        $this->assertSame('2026-08-01 18:30:00', $tenant->shop_closed_at->format('Y-m-d H:i:s'));
        $this->assertSame('august_sale', $tenant->campaign_code);
        $this->assertSame('2026-08-01 00:00:00', $tenant->campaign_starts_at->format('Y-m-d H:i:s'));
        $this->assertSame('2026-08-07 23:59:00', $tenant->campaign_ends_at->format('Y-m-d H:i:s'));
        $data = json_decode(
            DB::table('tenants')->where('id', 'babybright')->value('data'),
            true
        );

        $this->assertArrayHasKey('shop_closed_at', $data);
        $this->assertArrayHasKey('campaign_code', $data);
    }

    public function test_shop_closed_uses_saved_close_date_and_is_open_when_unset(): void
    {
        $tenant = $this->createTenant('babybright', 'Baby Bright');
        tenancy()->initialize($tenant);

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-01 17:59:59'));
        $this->assertFalse(ShopUtil::isShopClosed());

        $tenant->update(['shop_closed_at' => '2026-08-01 18:00:00']);

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-01 18:00:00'));
        $this->assertTrue(ShopUtil::isShopClosed());

        $tenant->update(['shop_closed_at' => null]);
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2031-01-01 00:00:00'));

        $this->assertFalse(ShopUtil::isShopClosed());
    }

    public function test_campaign_code_uses_saved_campaign_window(): void
    {
        $tenant = $this->createTenant('babybright', 'Baby Bright');
        $tenant->update([
            'campaign_code' => 'august_sale',
            'campaign_starts_at' => '2026-08-01 00:00:00',
            'campaign_ends_at' => '2026-08-07 23:59:59',
        ]);
        tenancy()->initialize($tenant);

        $this->assertSame('august_sale', ShopUtil::getCampaignCode(CarbonImmutable::parse('2026-08-03 12:00:00')));
        $this->assertNull(ShopUtil::getCampaignCode(CarbonImmutable::parse('2026-08-08 00:00:00')));
    }

    public function test_configured_campaign_code_override_still_takes_precedence(): void
    {
        config(['custom.shop_campaign_code' => 'forced_campaign']);

        $tenant = $this->createTenant('babybright', 'Baby Bright');
        $tenant->update([
            'campaign_code' => 'august_sale',
            'campaign_starts_at' => '2026-08-01 00:00:00',
            'campaign_ends_at' => '2026-08-07 23:59:59',
        ]);
        tenancy()->initialize($tenant);

        $this->assertSame('forced_campaign', ShopUtil::getCampaignCode(CarbonImmutable::parse('2027-01-01 12:00:00')));
    }

    public function test_shop_admin_edits_only_their_own_shop(): void
    {
        $this->createTenant('babybright', 'Baby Bright');
        $this->createTenant('gadzila', 'Gadzila');
        $shopAdmin = $this->createShopAdmin('babybright');

        $this->actingAs($shopAdmin, 'admin');
        $this->withSession(['admin_current_tenant_id' => 'gadzila']);

        Livewire::test(SettingsPage::class)
            ->assertSet('tenantId', 'babybright')
            ->set('enableShop', false)
            ->set('enableCoupon', true)
            ->set('shopClosedAt', '2026-09-01T18:00')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertFalse(Tenant::query()->findOrFail('babybright')->enable_shop);
        $this->assertTrue(Tenant::query()->findOrFail('gadzila')->enable_shop);
    }

    public function test_super_admin_edits_the_current_switched_shop(): void
    {
        $this->createTenant('babybright', 'Baby Bright');
        $this->createTenant('gadzila', 'Gadzila');
        $super = $this->createSuperAdmin();

        $this->actingAs($super, 'admin');
        $this->withSession(['admin_current_tenant_id' => 'gadzila']);

        Livewire::test(SettingsPage::class)
            ->assertSet('tenantId', 'gadzila')
            ->set('enableShop', false)
            ->set('enableCoupon', true)
            ->set('campaignCode', 'gadzila_sale')
            ->set('campaignStartsAt', '2026-09-01T00:00')
            ->set('campaignEndsAt', '2026-09-03T23:59')
            ->call('save')
            ->assertHasNoErrors();

        $babybright = Tenant::query()->findOrFail('babybright');
        $gadzila = Tenant::query()->findOrFail('gadzila');

        $this->assertTrue($babybright->enable_shop);
        $this->assertFalse($gadzila->enable_shop);
        $this->assertSame('gadzila_sale', $gadzila->campaign_code);
    }

    private function createTenant(string $id, string $name): Tenant
    {
        return Tenant::query()->create([
            'id' => $id,
            'name' => $name,
            'status' => 'active',
            'enable_shop' => true,
            'enable_coupon' => false,
        ]);
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

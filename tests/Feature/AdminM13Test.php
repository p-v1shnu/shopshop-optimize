<?php

namespace Tests\Feature;

use App\Livewire\Admin\CentralSettingsPage;
use App\Models\Admin;
use App\Models\Setting;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class AdminM13Test extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_view_and_update_central_settings(): void
    {
        $this->createTenant('babybright', 'Baby Bright');
        $super = $this->createSuperAdmin();
        Setting::query()->create([
            'title' => 'Old platform title',
            'facebook_cover_url' => 'https://assets.example/old-cover.jpg',
            'landing_page_url' => 'https://shopshop.example/old',
        ]);

        $this->actingAs($super, 'admin');
        $this->withSession(['admin_current_tenant_id' => 'babybright']);

        Livewire::test(CentralSettingsPage::class)
            ->assertSet('title', 'Old platform title')
            ->assertSet('facebookCoverUrl', 'https://assets.example/old-cover.jpg')
            ->assertSet('landingPageUrl', 'https://shopshop.example/old')
            ->set('title', 'ShopShop Laos')
            ->set('facebookCoverUrl', 'https://assets.example/new-cover.jpg')
            ->set('landingPageUrl', 'https://shopshop.example/landing')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('settings', 1);
        $this->assertDatabaseHas('settings', [
            'title' => 'ShopShop Laos',
            'facebook_cover_url' => 'https://assets.example/new-cover.jpg',
            'landing_page_url' => 'https://shopshop.example/landing',
        ]);
    }

    public function test_saving_when_no_settings_row_exists_creates_one_singleton_row_without_duplicates(): void
    {
        $this->createTenant('babybright', 'Baby Bright');
        $super = $this->createSuperAdmin();

        $this->actingAs($super, 'admin');
        $this->withSession(['admin_current_tenant_id' => 'babybright']);

        Livewire::test(CentralSettingsPage::class)
            ->set('title', 'First title')
            ->set('facebookCoverUrl', 'https://assets.example/first-cover.jpg')
            ->set('landingPageUrl', 'https://shopshop.example/first')
            ->call('save')
            ->assertHasNoErrors()
            ->set('title', 'Second title')
            ->set('facebookCoverUrl', 'https://assets.example/second-cover.jpg')
            ->set('landingPageUrl', 'https://shopshop.example/second')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('settings', 1);
        $this->assertDatabaseHas('settings', [
            'title' => 'Second title',
            'facebook_cover_url' => 'https://assets.example/second-cover.jpg',
            'landing_page_url' => 'https://shopshop.example/second',
        ]);
    }

    public function test_shop_admin_cannot_access_central_settings_route_or_nav(): void
    {
        $this->createTenant('babybright', 'Baby Bright');
        $shopAdmin = $this->createShopAdmin('babybright');
        $super = $this->createSuperAdmin();

        $this->actingAs($shopAdmin, 'admin');

        $this->get('http://admin.shopshop.test/admin')
            ->assertOk()
            ->assertDontSee('Central settings');
        $this->get('http://admin.shopshop.test/admin/central-settings')
            ->assertForbidden();

        $this->actingAs($super, 'admin');
        $this->withSession(['admin_current_tenant_id' => 'babybright']);

        $this->get('http://admin.shopshop.test/admin')
            ->assertOk()
            ->assertSee('Central settings');
        $this->get('http://admin.shopshop.test/admin/central-settings')
            ->assertOk()
            ->assertSee('Central settings');
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

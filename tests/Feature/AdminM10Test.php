<?php

namespace Tests\Feature;

use App\Livewire\Admin\BannersPage;
use App\Livewire\Frontend\PopupBanner;
use App\Models\Admin;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class AdminM10Test extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        tenancy()->end();

        parent::tearDown();
    }

    public function test_admin_can_upload_homepage_and_popup_banners_as_plain_url_arrays(): void
    {
        Storage::fake('s3');
        config(['filesystems.default' => 's3']);

        $this->createTenant('babybright', 'Baby Bright', 'babybright.shopshop.test');
        $admin = $this->createShopAdmin('babybright');

        $this->actingAs($admin, 'admin');
        $this->withSession(['admin_current_tenant_id' => 'babybright']);

        Livewire::test(BannersPage::class)
            ->set('homepageUploads', [
                UploadedFile::fake()->image('home-one.jpg', 1200, 600),
                UploadedFile::fake()->image('home-two.jpg', 1200, 600),
            ])
            ->call('addHomepageBanners')
            ->set('popupUploads', [
                UploadedFile::fake()->image('popup-one.jpg', 800, 800),
            ])
            ->call('addPopupBanners')
            ->assertHasNoErrors();

        $tenant = Tenant::query()->findOrFail('babybright');

        $this->assertCount(2, $tenant->homepage_banners);
        $this->assertCount(1, $tenant->popup_banners);
        $this->assertTrue(collect($tenant->homepage_banners)->every(fn (mixed $banner): bool => is_string($banner)));
        $this->assertTrue(collect($tenant->popup_banners)->every(fn (mixed $banner): bool => is_string($banner)));
        $this->assertStringContainsString('/banners/babybright/homepage/', $tenant->homepage_banners[0]);
        $this->assertStringContainsString('/banners/babybright/popup/', $tenant->popup_banners[0]);

        $storedPath = ltrim(str_replace('/storage/', '', parse_url($tenant->homepage_banners[0], PHP_URL_PATH)), '/');
        Storage::disk('s3')->assertExists($storedPath);
    }

    public function test_admin_can_reorder_and_remove_banners(): void
    {
        $tenant = $this->createTenant('babybright', 'Baby Bright', 'babybright.shopshop.test');
        $tenant->update([
            'homepage_banners' => [
                'https://assets.example.test/home-1.jpg',
                'https://assets.example.test/home-2.jpg',
                'https://assets.example.test/home-3.jpg',
            ],
            'popup_banners' => [
                'https://assets.example.test/popup-1.jpg',
                'https://assets.example.test/popup-2.jpg',
            ],
        ]);
        $admin = $this->createShopAdmin('babybright');

        $this->actingAs($admin, 'admin');
        $this->withSession(['admin_current_tenant_id' => 'babybright']);

        Livewire::test(BannersPage::class)
            ->call('moveHomepageBanner', 2, 'up')
            ->call('removeHomepageBanner', 0)
            ->call('movePopupBanner', 0, 'down')
            ->call('removePopupBanner', 1)
            ->assertHasNoErrors();

        $tenant->refresh();

        $this->assertSame([
            'https://assets.example.test/home-3.jpg',
            'https://assets.example.test/home-2.jpg',
        ], $tenant->homepage_banners);
        $this->assertSame([
            'https://assets.example.test/popup-2.jpg',
        ], $tenant->popup_banners);
    }

    public function test_shop_admin_edits_only_their_own_shop_banners(): void
    {
        $babybright = $this->createTenant('babybright', 'Baby Bright', 'babybright.shopshop.test');
        $gadzila = $this->createTenant('gadzila', 'Gadzila', 'gadzila.shopshop.test');
        $babybright->update(['homepage_banners' => ['https://assets.example.test/baby.jpg']]);
        $gadzila->update(['homepage_banners' => ['https://assets.example.test/gadzila.jpg']]);
        $admin = $this->createShopAdmin('babybright');

        $this->actingAs($admin, 'admin');
        $this->withSession(['admin_current_tenant_id' => 'gadzila']);

        Livewire::test(BannersPage::class)
            ->assertSet('tenantId', 'babybright')
            ->call('removeHomepageBanner', 0)
            ->assertHasNoErrors();

        $this->assertSame([], $babybright->refresh()->homepage_banners);
        $this->assertSame(['https://assets.example.test/gadzila.jpg'], $gadzila->refresh()->homepage_banners);
    }

    public function test_super_admin_edits_the_switched_shop_banners(): void
    {
        $babybright = $this->createTenant('babybright', 'Baby Bright', 'babybright.shopshop.test');
        $gadzila = $this->createTenant('gadzila', 'Gadzila', 'gadzila.shopshop.test');
        $babybright->update(['popup_banners' => ['https://assets.example.test/baby-popup.jpg']]);
        $gadzila->update(['popup_banners' => ['https://assets.example.test/gadzila-popup.jpg']]);
        $super = $this->createSuperAdmin();

        $this->actingAs($super, 'admin');
        $this->withSession(['admin_current_tenant_id' => 'gadzila']);

        Livewire::test(BannersPage::class)
            ->assertSet('tenantId', 'gadzila')
            ->call('removePopupBanner', 0)
            ->assertHasNoErrors();

        $this->assertSame(['https://assets.example.test/baby-popup.jpg'], $babybright->refresh()->popup_banners);
        $this->assertSame([], $gadzila->refresh()->popup_banners);
    }

    public function test_banners_page_is_accessible_to_shop_admin_and_super_admin(): void
    {
        $this->createTenant('babybright', 'Baby Bright', 'babybright.shopshop.test');
        $shopAdmin = $this->createShopAdmin('babybright');
        $super = $this->createSuperAdmin();

        $this->actingAs($shopAdmin, 'admin');
        $this->get('http://admin.shopshop.test/admin/banners')
            ->assertOk()
            ->assertSee('Banners');

        $this->actingAs($super, 'admin');
        $this->withSession(['admin_current_tenant_id' => 'babybright']);
        $this->get('http://admin.shopshop.test/admin/banners')
            ->assertOk()
            ->assertSee('Banners');
    }

    public function test_storefront_homepage_and_popup_render_admin_saved_banner_urls(): void
    {
        $tenant = $this->createTenant('babybright', 'Baby Bright', 'babybright.shopshop.test');
        $tenant->update([
            'homepage_banners' => ['https://assets.example.test/home-saved.jpg'],
            'popup_banners' => ['https://assets.example.test/popup-saved.jpg'],
        ]);

        $this->get('http://babybright.shopshop.test/shop')
            ->assertOk()
            ->assertSee('https://assets.example.test/home-saved.jpg', false);

        Cache::flush();
        tenancy()->initialize($tenant);
        Livewire::test(PopupBanner::class)
            ->assertSet('images', ['https://assets.example.test/popup-saved.jpg'])
            ->assertSet('show', true);
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

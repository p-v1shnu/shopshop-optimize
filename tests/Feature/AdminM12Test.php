<?php

namespace Tests\Feature;

use App\Livewire\Admin\SearchAnalyticsPage;
use App\Models\Admin;
use App\Models\ShopProduct;
use App\Models\ShopUserSearch;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class AdminM12Test extends TestCase
{
    use RefreshDatabase;

    public function test_top_search_terms_are_grouped_counted_ordered_and_date_filtered(): void
    {
        $this->createTenant('babybright', 'Baby Bright');
        $admin = $this->createShopAdmin('babybright');
        $user = $this->createUser('babybright');

        $this->createSearch($user, 'lipstick', '2026-07-01 10:00:00');
        $this->createSearch($user, 'serum', '2026-07-02 10:00:00');
        $this->createSearch($user, 'serum', '2026-07-03 10:00:00');
        $this->createSearch($user, 'cream', '2026-07-04 10:00:00');
        $this->createSearch($user, 'cream', '2026-07-05 10:00:00');
        $this->createSearch($user, 'cream', '2026-07-06 10:00:00');

        $this->actingAs($admin, 'admin');
        $this->withSession(['admin_current_tenant_id' => 'babybright']);

        Livewire::test(SearchAnalyticsPage::class)
            ->assertSeeInOrder(['cream', '3', 'serum', '2', 'lipstick', '1'])
            ->set('dateFrom', '2026-07-02')
            ->set('dateTo', '2026-07-04')
            ->assertSeeInOrder(['serum', '2', 'cream', '1'])
            ->assertDontSee('lipstick');
    }

    public function test_most_searched_products_are_scoped_and_ordered_by_total_search(): void
    {
        $this->createTenant('babybright', 'Baby Bright');
        $this->createTenant('gadzila', 'Gadzila');
        $admin = $this->createShopAdmin('babybright');

        $this->createProduct('babybright', 'Low Search Product', 'BB-LOW', 3);
        $this->createProduct('babybright', 'High Search Product', 'BB-HIGH', 99);
        $this->createProduct('babybright', 'Medium Search Product', 'BB-MID', 20);
        $this->createProduct('gadzila', 'Other Shop Product', 'GZ-HIGH', 200);

        $this->actingAs($admin, 'admin');
        $this->withSession(['admin_current_tenant_id' => 'babybright']);

        Livewire::test(SearchAnalyticsPage::class)
            ->assertSeeInOrder(['High Search Product', '99', 'Medium Search Product', '20', 'Low Search Product', '3'])
            ->assertDontSee('Other Shop Product');
    }

    public function test_shop_admin_sees_only_their_own_shop_search_data(): void
    {
        $this->createTenant('babybright', 'Baby Bright');
        $this->createTenant('gadzila', 'Gadzila');
        $babyUser = $this->createUser('babybright');
        $gadzilaUser = $this->createUser('gadzila');
        $admin = $this->createShopAdmin('babybright');

        $this->createSearch($babyUser, 'baby-only-term', '2026-07-01 10:00:00');
        $this->createSearch($gadzilaUser, 'gadzila-only-term', '2026-07-01 10:00:00');
        $this->createProduct('babybright', 'Baby Product', 'BB-PROD', 10);
        $this->createProduct('gadzila', 'Gadzila Product', 'GZ-PROD', 100);

        $this->actingAs($admin, 'admin');
        $this->withSession(['admin_current_tenant_id' => 'gadzila']);

        Livewire::test(SearchAnalyticsPage::class)
            ->assertSet('tenantId', 'babybright')
            ->assertSee('baby-only-term')
            ->assertSee('Baby Product')
            ->assertDontSee('gadzila-only-term')
            ->assertDontSee('Gadzila Product');
    }

    public function test_super_admin_sees_the_switched_shop_search_data(): void
    {
        $this->createTenant('babybright', 'Baby Bright');
        $this->createTenant('gadzila', 'Gadzila');
        $babyUser = $this->createUser('babybright');
        $gadzilaUser = $this->createUser('gadzila');
        $super = $this->createSuperAdmin();

        $this->createSearch($babyUser, 'baby-only-term', '2026-07-01 10:00:00');
        $this->createSearch($gadzilaUser, 'gadzila-only-term', '2026-07-01 10:00:00');
        $this->createProduct('babybright', 'Baby Product', 'BB-PROD', 10);
        $this->createProduct('gadzila', 'Gadzila Product', 'GZ-PROD', 100);

        $this->actingAs($super, 'admin');
        $this->withSession(['admin_current_tenant_id' => 'gadzila']);

        Livewire::test(SearchAnalyticsPage::class)
            ->assertSet('tenantId', 'gadzila')
            ->assertSee('gadzila-only-term')
            ->assertSee('Gadzila Product')
            ->assertDontSee('baby-only-term')
            ->assertDontSee('Baby Product');
    }

    public function test_search_analytics_route_and_nav_are_available_to_shop_and_super_admins(): void
    {
        $this->createTenant('babybright', 'Baby Bright');
        $shopAdmin = $this->createShopAdmin('babybright');
        $super = $this->createSuperAdmin();

        $this->actingAs($shopAdmin, 'admin');
        $this->get('http://admin.shopshop.test/admin')
            ->assertOk()
            ->assertSee('Search analytics');
        $this->get('http://admin.shopshop.test/admin/search-analytics')
            ->assertOk()
            ->assertSee('Search analytics');

        $this->actingAs($super, 'admin');
        $this->withSession(['admin_current_tenant_id' => 'babybright']);
        $this->get('http://admin.shopshop.test/admin')
            ->assertOk()
            ->assertSee('Search analytics');
        $this->get('http://admin.shopshop.test/admin/search-analytics')
            ->assertOk()
            ->assertSee('Search analytics');
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

    private function createUser(string $tenantId): User
    {
        return User::query()->create([
            'tenant_id' => $tenantId,
            'type' => 'phone',
            'phone' => '20'.random_int(10000000, 99999999),
            'name' => 'Customer',
            'role' => 'user',
            'status' => 'active',
        ]);
    }

    private function createSearch(User $user, string $term, string $createdAt): ShopUserSearch
    {
        return ShopUserSearch::query()->create([
            'tenant_id' => $user->tenant_id,
            'user_id' => $user->id,
            'search_term' => $term,
            'status' => 'active',
            'created_at' => $createdAt,
        ]);
    }

    private function createProduct(string $tenantId, string $name, string $sku, int $totalSearch): ShopProduct
    {
        return ShopProduct::query()->create([
            'tenant_id' => $tenantId,
            'name' => $name,
            'images' => [
                ['filename' => "https://assets.example.test/{$sku}.jpg", 'is_cover' => true],
            ],
            'normal_price' => 120000,
            'price' => 99000,
            'sku' => $sku,
            'total_search' => $totalSearch,
            'available_quantity' => 10,
            'sort_no' => 1,
            'status' => 'active',
        ]);
    }
}

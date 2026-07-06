<?php

namespace Tests\Feature;

use App\Livewire\Admin\LoginPage;
use App\Models\Admin;
use App\Models\ShopProduct;
use App\Models\Tenant;
use App\Support\AdminTenantScope;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class AdminM0Test extends TestCase
{
    use RefreshDatabase;

    public function test_admin_seeder_creates_the_first_super_admin(): void
    {
        $this->seed(AdminSeeder::class);

        $admin = Admin::query()->where('email', 'pele@bizgital.com')->first();

        $this->assertNotNull($admin);
        $this->assertSame('super', $admin->role);
        $this->assertNull($admin->tenant_id);
        $this->assertSame('active', $admin->status);
        $this->assertTrue(Hash::check('ChangeMe!AdminM0', $admin->password));
    }

    public function test_guest_is_redirected_to_admin_login(): void
    {
        $this->get('http://admin.shopshop.test/admin')
            ->assertRedirect('http://admin.shopshop.test/admin/login');
    }

    public function test_super_admin_can_login_switch_current_shop_and_logout(): void
    {
        $this->createTenant('babybright', 'Baby Bright');
        $this->createTenant('gadzila', 'Gadzila');

        $admin = Admin::query()->create([
            'name' => 'Super Admin',
            'email' => 'super@example.com',
            'password' => Hash::make('password'),
            'role' => 'super',
            'tenant_id' => null,
            'status' => 'active',
        ]);

        // Login through the LoginPage Livewire component (the real login path).
        Livewire::test(LoginPage::class)
            ->set('email', $admin->email)
            ->set('password', 'password')
            ->call('login')
            ->assertRedirect(route('admin.dashboard'));

        // Ensure the following HTTP requests run as this authenticated admin.
        $this->actingAs($admin, 'admin');

        $this->get('http://admin.shopshop.test/admin')
            ->assertOk()
            ->assertSee('ShopShop Admin')
            ->assertSee('Current shop')
            ->assertSee('Baby Bright')
            ->assertSessionHas('admin_current_tenant_id', 'babybright');

        $this->post('http://admin.shopshop.test/admin/current-shop', [
            'tenant_id' => 'gadzila',
        ])->assertRedirect('http://admin.shopshop.test/admin')
            ->assertSessionHas('admin_current_tenant_id', 'gadzila');

        $this->post('http://admin.shopshop.test/admin/logout')
            ->assertRedirect('http://admin.shopshop.test/admin/login');

        $this->assertGuest('admin');
    }

    public function test_shop_admin_is_locked_to_own_shop_and_cannot_switch(): void
    {
        $this->createTenant('babybright', 'Baby Bright');
        $this->createTenant('gadzila', 'Gadzila');

        $admin = Admin::query()->create([
            'name' => 'Shop Admin',
            'email' => 'shop@example.com',
            'password' => Hash::make('password'),
            'role' => 'shop',
            'tenant_id' => 'babybright',
            'status' => 'active',
        ]);

        $this->actingAs($admin, 'admin');

        $this->get('http://admin.shopshop.test/admin')
            ->assertOk()
            ->assertSee('Baby Bright')
            ->assertDontSee('Current shop')
            ->assertDontSee('Gadzila')
            ->assertSessionHas('admin_current_tenant_id', 'babybright');

        $this->post('http://admin.shopshop.test/admin/current-shop', [
            'tenant_id' => 'gadzila',
        ])->assertForbidden()
            ->assertSessionHas('admin_current_tenant_id', 'babybright');
    }

    public function test_admin_tenant_scope_filters_queries_to_current_shop(): void
    {
        $this->createTenant('babybright', 'Baby Bright');
        $this->createTenant('gadzila', 'Gadzila');
        $this->createProduct('babybright', 'Baby Product');
        $this->createProduct('gadzila', 'Gadzila Product');

        $admin = Admin::query()->create([
            'name' => 'Shop Admin',
            'email' => 'shop@example.com',
            'password' => Hash::make('password'),
            'role' => 'shop',
            'tenant_id' => 'babybright',
            'status' => 'active',
        ]);

        $this->actingAs($admin, 'admin');
        $this->withSession(['admin_current_tenant_id' => 'babybright']);

        $products = app(AdminTenantScope::class)
            ->apply(ShopProduct::query())
            ->pluck('name')
            ->all();

        $this->assertSame(['Baby Product'], $products);
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

    private function createProduct(string $tenantId, string $name): ShopProduct
    {
        return ShopProduct::query()->create([
            'tenant_id' => $tenantId,
            'name' => $name,
            'images' => [],
            'price' => 1,
            'sku' => $tenantId.'-sku',
            'available_quantity' => 1,
            'sort_no' => 1,
            'status' => 'active',
        ]);
    }
}

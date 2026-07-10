<?php

namespace Tests\Feature;

use App\Livewire\Admin\DashboardPage;
use App\Models\Admin;
use App\Models\ShopOrder;
use App\Models\ShopProduct;
use App\Models\Tenant;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class AdminM5Test extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_dashboard_shows_current_shop_sales_order_shipping_and_low_stock_metrics(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-07-06 14:00:00', config('app.timezone')));

        $this->createTenant('babybright', 'Baby Bright');
        $admin = $this->createShopAdmin('babybright');

        $this->createOrder('babybright', 'today-paid-1', '2026-07-06 09:00:00', 100000, 'paid', 'pending');
        $this->createOrder('babybright', 'today-paid-2', '2026-07-06 13:00:00', 50000, 'paid', 'shipping');
        $this->createOrder('babybright', 'today-pending', '2026-07-06 12:00:00', 999999, 'pending', 'pending');
        $this->createOrder('babybright', 'month-paid', '2026-07-01 10:00:00', 25000, 'paid', 'pending');
        $this->createOrder('babybright', 'previous-month-paid', '2026-06-30 23:59:59', 70000, 'paid', 'pending');

        $this->createProduct('babybright', 'Low 1', 'BB-LOW-1', 0);
        $this->createProduct('babybright', 'Low 2', 'BB-LOW-2', 5);
        $this->createProduct('babybright', 'Healthy', 'BB-OK', 6);

        $this->actingAs($admin, 'admin');
        $this->withSession(['admin_current_tenant_id' => 'babybright']);

        Livewire::test(DashboardPage::class)
            ->assertSee('Today sales')
            ->assertSee('150,000.00 LAK')
            ->assertSee('2 paid orders')
            ->assertSee('Month sales')
            ->assertSee('175,000.00 LAK')
            ->assertSee('3 paid orders')
            ->assertSee('Pending to ship')
            ->assertSee('3 orders')
            ->assertSee('Low-stock products')
            ->assertSee('2 products')
            ->assertSee('Threshold: 5');
    }

    public function test_dashboard_metrics_are_scoped_to_current_shop(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-07-06 14:00:00', config('app.timezone')));

        $this->createTenant('babybright', 'Baby Bright');
        $this->createTenant('gadzila', 'Gadzila');
        $super = $this->createSuperAdmin();

        $this->createOrder('babybright', 'bb-paid', '2026-07-06 09:00:00', 100000, 'paid', 'pending');
        $this->createOrder('gadzila', 'gz-paid', '2026-07-06 09:00:00', 900000, 'paid', 'pending');
        $this->createProduct('babybright', 'Baby Low', 'BB-LOW', 5);
        $this->createProduct('gadzila', 'Gadzila Low 1', 'GZ-LOW-1', 0);
        $this->createProduct('gadzila', 'Gadzila Low 2', 'GZ-LOW-2', 1);

        $this->actingAs($super, 'admin');
        $this->withSession(['admin_current_tenant_id' => 'babybright']);

        Livewire::test(DashboardPage::class)
            ->assertSee('100,000.00 LAK')
            ->assertDontSee('900,000.00 LAK')
            ->assertSee('1 products')
            ->assertDontSee('2 products');
    }

    public function test_dashboard_handles_no_current_shop_selected(): void
    {
        $super = $this->createSuperAdmin();

        $this->actingAs($super, 'admin');
        $this->withSession(['admin_current_tenant_id' => null]);

        Livewire::test(DashboardPage::class)
            ->assertSee('No shop selected')
            ->assertSee('Choose a shop from the switcher to view dashboard metrics.');
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

    private function createOrder(
        string $tenantId,
        string $id,
        string $createdAt,
        int $paymentAmount,
        string $paymentStatus,
        string $shippingStatus
    ): ShopOrder {
        $user = $this->createUser($tenantId);

        return ShopOrder::query()->create([
            'tenant_id' => $tenantId,
            'id' => $tenantId.'-'.$id,
            'user_id' => $user->id,
            'order_amount' => $paymentAmount,
            'shipping_amount' => 0,
            'coupon_amount' => 0,
            'payment_amount' => $paymentAmount,
            'payment_uuid' => 'pay-'.$tenantId.'-'.$id,
            'payment_status' => $paymentStatus,
            'payment_channel' => 'bcel',
            'shipping_fee_type' => 'prepaid',
            'shipping_channel' => 'hal',
            'shipping_name' => 'Customer',
            'shipping_phone' => '205551111',
            'shipping_province' => 'VT',
            'shipping_district' => 'Vientiane',
            'shipping_village' => 'Nongbone',
            'shipping_status' => $shippingStatus,
            'order_code' => strtoupper($id),
            'created_at' => $createdAt,
        ]);
    }

    private function createProduct(string $tenantId, string $name, string $sku, int $availableQuantity): ShopProduct
    {
        return ShopProduct::query()->create([
            'tenant_id' => $tenantId,
            'name' => $name,
            'images' => [],
            'price' => 1,
            'sku' => $sku,
            'available_quantity' => $availableQuantity,
            'status' => 'active',
        ]);
    }
}

<?php

namespace Tests\Feature;

use App\Livewire\Admin\CouponsPage;
use App\Models\Admin;
use App\Models\ShopCoupon;
use App\Models\ShopOrder;
use App\Models\ShopOrderCoupon;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class AdminM6Test extends TestCase
{
    use RefreshDatabase;

    public function test_coupon_list_is_scoped_searchable_type_filtered_status_filtered_and_paginated(): void
    {
        $this->createTenant('babybright', 'Baby Bright');
        $this->createTenant('gadzila', 'Gadzila');
        $admin = $this->createShopAdmin('babybright');

        $this->createCoupon('babybright', 'SAVE10', 'fixed', 'active');
        $this->createCoupon('babybright', 'PERCENT20', 'percentage', 'inactive');
        $this->createCoupon('gadzila', 'SAVE10-GZ', 'fixed', 'active');

        $this->actingAs($admin, 'admin');
        $this->withSession(['admin_current_tenant_id' => 'babybright']);

        Livewire::test(CouponsPage::class)
            ->assertSee('SAVE10')
            ->assertSee('PERCENT20')
            ->assertDontSee('SAVE10-GZ')
            ->set('search', 'percent')
            ->assertDontSee('SAVE10')
            ->assertSee('PERCENT20')
            ->set('typeFilter', 'fixed')
            ->assertDontSee('PERCENT20')
            ->set('search', '')
            ->assertSee('SAVE10')
            ->set('statusFilter', 'inactive')
            ->assertDontSee('SAVE10');
    }

    public function test_admin_can_create_and_edit_fixed_and_percentage_coupons(): void
    {
        $this->createTenant('babybright', 'Baby Bright');
        $admin = $this->createShopAdmin('babybright');
        $customer = $this->createUser('babybright', 'Customer One', '205551111');

        $this->actingAs($admin, 'admin');
        $this->withSession(['admin_current_tenant_id' => 'babybright']);

        Livewire::test(CouponsPage::class)
            ->call('create')
            ->set('code', 'SAVE10')
            ->set('type', 'fixed')
            ->set('amount', '10000')
            ->set('startedAt', '2026-07-01T00:00')
            ->set('endedAt', '2026-07-31T23:59')
            ->set('totalQuantity', 100)
            ->set('availableQuantity', 80)
            ->set('userDailyLimit', 1)
            ->set('minimumOrderAmount', '50000')
            ->set('status', 'active')
            ->set('remark', 'Launch coupon')
            ->set('userId', (string) $customer->id)
            ->call('save')
            ->assertHasNoErrors();

        $coupon = ShopCoupon::query()->where('code', 'SAVE10')->firstOrFail();

        $this->assertSame('babybright', $coupon->tenant_id);
        $this->assertSame($customer->id, $coupon->user_id);
        $this->assertSame('10000.00', $coupon->amount);
        $this->assertSame('2026-07-01 00:00:00', $coupon->started_at->format('Y-m-d H:i:s'));

        Livewire::test(CouponsPage::class)
            ->call('edit', $coupon->id)
            ->set('code', 'PCT20')
            ->set('type', 'percentage')
            ->set('amount', '20')
            ->set('availableQuantity', 50)
            ->set('userId', '')
            ->call('save')
            ->assertHasNoErrors();

        $coupon->refresh();

        $this->assertSame('PCT20', $coupon->code);
        $this->assertSame('percentage', $coupon->type);
        $this->assertSame('20.00', $coupon->amount);
        $this->assertNull($coupon->user_id);
        $this->assertSame(50, $coupon->available_quantity);
    }

    public function test_coupon_code_is_unique_per_tenant_and_user_must_belong_to_current_shop(): void
    {
        $this->createTenant('babybright', 'Baby Bright');
        $this->createTenant('gadzila', 'Gadzila');
        $admin = $this->createShopAdmin('babybright');
        $otherShopCustomer = $this->createUser('gadzila', 'Other Customer', '205559999');

        $this->createCoupon('babybright', 'SAVE10');
        $this->createCoupon('gadzila', 'SAVE10');

        $this->actingAs($admin, 'admin');
        $this->withSession(['admin_current_tenant_id' => 'babybright']);

        Livewire::test(CouponsPage::class)
            ->call('create')
            ->set('code', 'SAVE10')
            ->set('type', 'fixed')
            ->set('amount', '1000')
            ->set('totalQuantity', 10)
            ->set('availableQuantity', 10)
            ->set('userDailyLimit', 1)
            ->set('minimumOrderAmount', '0')
            ->set('status', 'active')
            ->call('save')
            ->assertHasErrors(['code'])
            ->set('code', 'PRIVATE')
            ->set('userId', (string) $otherShopCustomer->id)
            ->call('save')
            ->assertHasErrors(['userId']);
    }

    public function test_trigger_aligned_amount_errors_surface_as_validation_errors(): void
    {
        $this->createTenant('babybright', 'Baby Bright');
        $admin = $this->createShopAdmin('babybright');

        $this->actingAs($admin, 'admin');
        $this->withSession(['admin_current_tenant_id' => 'babybright']);

        Livewire::test(CouponsPage::class)
            ->call('create')
            ->set('code', 'BADPERCENT')
            ->set('type', 'percentage')
            ->set('amount', '101')
            ->set('totalQuantity', 10)
            ->set('availableQuantity', 10)
            ->set('userDailyLimit', 1)
            ->set('minimumOrderAmount', '0')
            ->set('status', 'active')
            ->call('save')
            ->assertHasErrors(['amount'])
            ->set('type', 'fixed')
            ->set('amount', '-1')
            ->call('save')
            ->assertHasErrors(['amount']);
    }

    public function test_usage_history_is_listed_for_selected_coupon(): void
    {
        $this->createTenant('babybright', 'Baby Bright');
        $admin = $this->createShopAdmin('babybright');
        $customer = $this->createUser('babybright', 'Customer One', '205551111');
        $coupon = $this->createCoupon('babybright', 'SAVE10');
        $order = $this->createOrder('babybright', $customer, 'BB-1001');

        ShopOrderCoupon::query()->create([
            'tenant_id' => 'babybright',
            'shop_order_id' => $order->id,
            'shop_coupon_id' => $coupon->id,
            'user_id' => $customer->id,
            'coupon_code' => 'SAVE10',
            'coupon_type' => 'fixed',
            'coupon_amount' => 10000,
            'discount_amount' => 10000,
            'before_discount_amount' => 100000,
            'minimum_order_amount' => 0,
            'user_daily_limit' => 1,
            'remark' => 'Used at checkout',
        ]);

        $this->actingAs($admin, 'admin');
        $this->withSession(['admin_current_tenant_id' => 'babybright']);

        Livewire::test(CouponsPage::class)
            ->call('edit', $coupon->id)
            ->assertSee('Usage history')
            ->assertSee('BB-1001')
            ->assertSee('Customer One')
            ->assertSee('10,000.00');
    }

    public function test_shop_admin_cannot_access_another_shops_coupon(): void
    {
        $this->createTenant('babybright', 'Baby Bright');
        $this->createTenant('gadzila', 'Gadzila');
        $admin = $this->createShopAdmin('babybright');
        $otherCoupon = $this->createCoupon('gadzila', 'GZONLY');

        $this->actingAs($admin, 'admin');
        $this->withSession(['admin_current_tenant_id' => 'babybright']);

        Livewire::test(CouponsPage::class)
            ->call('edit', $otherCoupon->id)
            ->assertStatus(404);
    }

    private function createTenant(string $id, string $name): Tenant
    {
        return Tenant::query()->create([
            'id' => $id,
            'name' => $name,
            'status' => 'active',
            'enable_shop' => true,
            'enable_coupon' => true,
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

    private function createUser(string $tenantId, string $name, string $phone, string $role = 'user'): User
    {
        return User::query()->create([
            'tenant_id' => $tenantId,
            'type' => 'phone',
            'phone' => $phone,
            'name' => $name,
            'role' => $role,
            'status' => 'active',
        ]);
    }

    private function createCoupon(
        string $tenantId,
        string $code,
        string $type = 'fixed',
        string $status = 'active'
    ): ShopCoupon {
        return ShopCoupon::query()->create([
            'tenant_id' => $tenantId,
            'status' => $status,
            'code' => $code,
            'type' => $type,
            'amount' => $type === 'percentage' ? 20 : 10000,
            'total_quantity' => 100,
            'available_quantity' => 100,
            'user_daily_limit' => 1,
            'minimum_order_amount' => 0,
        ]);
    }

    private function createOrder(string $tenantId, User $user, string $orderCode): ShopOrder
    {
        return ShopOrder::query()->create([
            'tenant_id' => $tenantId,
            'id' => $tenantId.'-'.$orderCode,
            'user_id' => $user->id,
            'order_amount' => 100000,
            'shipping_amount' => 0,
            'coupon_amount' => 10000,
            'payment_amount' => 90000,
            'payment_uuid' => 'pay-'.$tenantId.'-'.$orderCode,
            'payment_status' => 'paid',
            'payment_channel' => 'bcel',
            'shipping_fee_type' => 'prepaid',
            'shipping_channel' => 'hal',
            'shipping_name' => $user->name,
            'shipping_phone' => $user->phone,
            'shipping_province' => 'VT',
            'shipping_district' => 'Vientiane',
            'shipping_village' => 'Nongbone',
            'shipping_status' => 'pending',
            'order_code' => $orderCode,
        ]);
    }
}

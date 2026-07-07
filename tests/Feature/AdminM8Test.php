<?php

namespace Tests\Feature;

use App\Livewire\Admin\CustomersPage;
use App\Livewire\Frontend\OtpLoginModal;
use App\Models\Admin;
use App\Models\ShopOrder;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;
use Tzsk\Otp\Facades\Otp;

class AdminM8Test extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        tenancy()->end();

        parent::tearDown();
    }

    public function test_customer_list_is_scoped_to_current_shop_role_user_searchable_and_paginated(): void
    {
        $this->createTenant('babybright', 'Baby Bright');
        $this->createTenant('gadzila', 'Gadzila');
        $admin = $this->createShopAdmin('babybright');

        $this->createUser('babybright', 'Alice Baby', '205551001');
        $this->createUser('babybright', 'Bob Baby', '205551002');
        $this->createUser('babybright', 'Staff Account', '205551003', role: 'staff');
        $this->createUser('gadzila', 'Alice Gadzila', '205559999');

        $this->actingAs($admin, 'admin');
        $this->withSession(['admin_current_tenant_id' => 'babybright']);

        Livewire::test(CustomersPage::class)
            ->assertSee('Alice Baby')
            ->assertSee('Bob Baby')
            ->assertDontSee('Staff Account')
            ->assertDontSee('Alice Gadzila')
            ->set('search', '205551002')
            ->assertDontSee('Alice Baby')
            ->assertSee('Bob Baby')
            ->set('search', 'alice')
            ->assertSee('Alice Baby')
            ->assertDontSee('Bob Baby');
    }

    public function test_customer_detail_shows_read_only_profile_and_customer_orders(): void
    {
        $this->createTenant('babybright', 'Baby Bright');
        $admin = $this->createShopAdmin('babybright');
        $customer = $this->createUser('babybright', 'Alice Baby', '205551001', attributes: [
            'gender' => 'F',
            'dob' => '1995-05-10',
            'province' => 'VT',
            'district' => 'Chanthabouly',
            'village' => 'Nongbone',
        ]);
        $this->createOrder('babybright', $customer, 'BB-1001', '2026-07-01 10:00:00', paymentAmount: 150000);
        $this->createOrder('babybright', $customer, 'BB-1002', '2026-07-02 10:00:00', paymentStatus: 'pending', shippingStatus: 'shipping', paymentAmount: 250000);

        $this->actingAs($admin, 'admin');
        $this->withSession(['admin_current_tenant_id' => 'babybright']);

        Livewire::test(CustomersPage::class)
            ->call('selectCustomer', $customer->id)
            ->assertSee('Alice Baby')
            ->assertSee('205551001')
            ->assertSee('F')
            ->assertSee('1995-05-10')
            ->assertSee('VT')
            ->assertSee('Chanthabouly')
            ->assertSee('Nongbone')
            ->assertSee('BB-1001')
            ->assertSee('BB-1002')
            ->assertSee('Paid')
            ->assertSee('Pending')
            ->assertSee('Shipping')
            ->assertSee('250,000.00');
    }

    public function test_ban_sets_inactive_banned_at_and_logs_required_remark_without_changing_profile(): void
    {
        $this->createTenant('babybright', 'Baby Bright');
        $admin = $this->createShopAdmin('babybright');
        $customer = $this->createUser('babybright', 'Alice Baby', '205551001', attributes: [
            'province' => 'VT',
            'district' => 'Chanthabouly',
            'village' => 'Nongbone',
        ]);

        $this->actingAs($admin, 'admin');
        $this->withSession(['admin_current_tenant_id' => 'babybright']);

        Livewire::test(CustomersPage::class)
            ->call('selectCustomer', $customer->id)
            ->call('banCustomer')
            ->assertHasErrors(['banRemark'])
            ->set('banRemark', 'Fraudulent payment attempts')
            ->call('banCustomer')
            ->assertHasNoErrors();

        $customer->refresh();

        $this->assertSame('inactive', $customer->status);
        $this->assertNotNull($customer->banned_at);
        $this->assertStringContainsString('Fraudulent payment attempts', $customer->remark);
        $this->assertStringContainsString($admin->email, $customer->remark);
        $this->assertSame('Alice Baby', $customer->name);
        $this->assertSame('205551001', $customer->phone);
        $this->assertSame('VT', $customer->province);
        $this->assertSame('Chanthabouly', $customer->district);
        $this->assertSame('Nongbone', $customer->village);
    }

    public function test_unban_reactivates_customer_and_clears_banned_at(): void
    {
        $this->createTenant('babybright', 'Baby Bright');
        $admin = $this->createShopAdmin('babybright');
        $customer = $this->createUser('babybright', 'Alice Baby', '205551001', attributes: [
            'status' => 'inactive',
            'banned_at' => '2026-07-01 10:00:00',
            'remark' => 'Banned: Fraudulent payment attempts',
        ]);

        $this->actingAs($admin, 'admin');
        $this->withSession(['admin_current_tenant_id' => 'babybright']);

        Livewire::test(CustomersPage::class)
            ->call('selectCustomer', $customer->id)
            ->call('unbanCustomer')
            ->assertHasNoErrors();

        $customer->refresh();

        $this->assertSame('active', $customer->status);
        $this->assertNull($customer->banned_at);
    }

    public function test_banned_customer_cannot_complete_otp_login(): void
    {
        $tenant = $this->createTenant('babybright', 'Baby Bright');
        tenancy()->initialize($tenant);

        $phone = '205551001';
        $this->createUser('babybright', 'Alice Baby', $phone, attributes: [
            'status' => 'inactive',
            'banned_at' => '2026-07-01 10:00:00',
        ]);
        $otp = Otp::digits(6)->expiry(30)->generate(hash('sha256', $phone));

        Livewire::test(OtpLoginModal::class)
            ->set('phone', $phone)
            ->set('otp', $otp)
            ->call('verifyOtp');

        $this->assertGuest();
    }

    public function test_shop_admin_cannot_view_ban_or_unban_another_shops_customer(): void
    {
        $this->createTenant('babybright', 'Baby Bright');
        $this->createTenant('gadzila', 'Gadzila');
        $admin = $this->createShopAdmin('babybright');
        $otherCustomer = $this->createUser('gadzila', 'Other Customer', '205559999');

        $this->actingAs($admin, 'admin');
        $this->withSession(['admin_current_tenant_id' => 'babybright']);

        Livewire::test(CustomersPage::class)
            ->call('selectCustomer', $otherCustomer->id)
            ->assertStatus(404);

        Livewire::test(CustomersPage::class)
            ->set('banRemark', 'Should not cross shops')
            ->call('banCustomer', $otherCustomer->id)
            ->assertStatus(404);

        Livewire::test(CustomersPage::class)
            ->call('unbanCustomer', $otherCustomer->id)
            ->assertStatus(404);
    }

    public function test_admin_layout_links_to_customers(): void
    {
        $this->createTenant('babybright', 'Baby Bright');
        $admin = $this->createShopAdmin('babybright');

        $this->actingAs($admin, 'admin');

        $this->get('http://admin.shopshop.test/admin')
            ->assertOk()
            ->assertSee('Customers')
            ->assertSee(route('admin.customers'), false);
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

    private function createUser(
        string $tenantId,
        string $name,
        string $phone,
        string $role = 'user',
        array $attributes = []
    ): User {
        return User::query()->create(array_merge([
            'tenant_id' => $tenantId,
            'type' => 'phone',
            'phone' => $phone,
            'name' => $name,
            'role' => $role,
            'status' => 'active',
        ], $attributes));
    }

    private function createOrder(
        string $tenantId,
        User $user,
        string $orderCode,
        string $createdAt,
        string $paymentStatus = 'paid',
        string $shippingStatus = 'pending',
        int $paymentAmount = 100000
    ): ShopOrder {
        return ShopOrder::query()->create([
            'tenant_id' => $tenantId,
            'id' => $tenantId.'-'.$orderCode,
            'user_id' => $user->id,
            'order_amount' => $paymentAmount,
            'shipping_amount' => 0,
            'coupon_amount' => 0,
            'payment_amount' => $paymentAmount,
            'payment_uuid' => 'pay-'.$tenantId.'-'.$orderCode,
            'payment_status' => $paymentStatus,
            'payment_channel' => 'bcel',
            'shipping_fee_type' => 'prepaid',
            'shipping_channel' => 'hal',
            'shipping_name' => $user->name,
            'shipping_phone' => $user->phone,
            'shipping_province' => 'VT',
            'shipping_district' => 'Vientiane',
            'shipping_village' => 'Nongbone',
            'shipping_status' => $shippingStatus,
            'order_code' => $orderCode,
            'created_at' => $createdAt,
        ]);
    }
}

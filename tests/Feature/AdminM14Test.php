<?php

namespace Tests\Feature;

use App\Livewire\Admin\AdminAccountsPage;
use App\Livewire\Admin\AdminActivityLogsPage;
use App\Livewire\Admin\BannersPage;
use App\Livewire\Admin\BrandsPage;
use App\Livewire\Admin\LoginPage;
use App\Livewire\Admin\MyAccountPage;
use App\Livewire\Admin\OrdersPage;
use App\Livewire\Admin\ProductsPage;
use App\Livewire\Admin\SettingsPage;
use App\Models\Admin;
use App\Models\AdminActivityLog;
use App\Models\ShopOrder;
use App\Models\ShopOrderDetail;
use App\Models\ShopProduct;
use App\Models\Tenant;
use App\Models\User;
use App\Support\AdminActivityLogger;
use App\Support\AdminTenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

class AdminM14Test extends TestCase
{
    use RefreshDatabase;

    public function test_auth_and_admin_account_actions_write_one_activity_log_each(): void
    {
        $this->createTenant('babybright', 'Baby Bright');
        $super = $this->createSuperAdmin();
        $target = $this->createShopAdmin('babybright', 'target@example.com');

        Livewire::test(LoginPage::class)
            ->set('email', $super->email)
            ->set('password', 'password')
            ->call('login')
            ->assertHasNoErrors();

        $this->assertActivityLogged('admin.login', $super->id, null);

        $this->withSession(['admin_current_tenant_id' => 'babybright']);

        Livewire::test(MyAccountPage::class)
            ->set('currentPassword', 'password')
            ->set('newPassword', 'NewSecret123!')
            ->set('newPasswordConfirmation', 'NewSecret123!')
            ->call('changePassword')
            ->assertHasNoErrors();

        Livewire::test(AdminAccountsPage::class)
            ->set('createName', 'Created Admin')
            ->set('createEmail', 'created@example.com')
            ->set('createRole', 'shop')
            ->set('createTenantId', 'babybright')
            ->set('createPassword', 'Secret123!')
            ->call('createAdmin')
            ->assertHasNoErrors()
            ->set('resetAdminId', $target->id)
            ->set('resetPasswordValue', 'AnotherSecret123!')
            ->call('resetPassword')
            ->assertHasNoErrors()
            ->call('setStatus', $target->id, 'inactive')
            ->assertHasNoErrors();

        $created = Admin::query()->where('email', 'created@example.com')->firstOrFail();

        $this->assertActivityLogged('admin.password.changed', $super->id, 'babybright', $super);
        $this->assertActivityLogged('admin.created', $super->id, 'babybright', $created, [
            'email' => 'created@example.com',
            'role' => 'shop',
            'tenant_id' => 'babybright',
        ]);
        $this->assertActivityLogged('admin.password.reset', $super->id, 'babybright', $target, [
            'email' => 'target@example.com',
            'role' => 'shop',
            'tenant_id' => 'babybright',
        ]);
        $this->assertActivityLogged('admin.status.changed', $super->id, 'babybright', $target, [
            'email' => 'target@example.com',
            'old_status' => 'active',
            'new_status' => 'inactive',
        ]);
    }

    public function test_shop_order_settings_brand_and_banner_actions_write_activity_logs(): void
    {
        Storage::fake('s3');
        config(['filesystems.default' => 's3']);

        $this->createTenant('babybright', 'Baby Bright');
        $shopAdmin = $this->createShopAdmin('babybright', 'shop@example.com');
        $product = $this->createProduct('babybright', 'Glow Cream', 'BB-GLOW', 2);
        $cancelOrder = $this->createOrder('babybright', 'BB-CANCEL', 'pending');
        $this->createOrderDetail($cancelOrder, $product, 2, 99000);
        $refundOrder = $this->createOrder('babybright', 'BB-REFUND', 'paid');

        $this->actingAs($shopAdmin, 'admin');
        $this->withSession(['admin_current_tenant_id' => 'babybright']);

        Livewire::test(ProductsPage::class)
            ->call('edit', $product->id)
            ->set('stockType', 'UPDATE')
            ->set('stockQuantity', 5)
            ->set('stockRemark', 'Manual stock count')
            ->call('adjustStock')
            ->assertHasNoErrors();

        Livewire::test(OrdersPage::class)
            ->call('selectOrder', $cancelOrder->id)
            ->set('cancelRemark', 'Customer requested')
            ->call('cancelOrder')
            ->assertHasNoErrors()
            ->call('selectOrder', $refundOrder->id)
            ->set('refundReference', 'BANK-REF-1')
            ->set('refundNote', 'Refunded by bank')
            ->call('refundOrder')
            ->assertHasNoErrors();

        Livewire::test(SettingsPage::class)
            ->set('enableShop', false)
            ->set('enableCoupon', true)
            ->call('save')
            ->assertHasNoErrors();

        Livewire::test(BannersPage::class)
            ->set('homepageUploads', [UploadedFile::fake()->image('home.jpg', 1200, 600)])
            ->call('addHomepageBanners')
            ->call('removeHomepageBanner', 0)
            ->assertHasNoErrors();

        $this->assertActivityLogged('product.stock.adjusted', $shopAdmin->id, 'babybright', $product, [
            'tenant_id' => 'babybright',
            'type' => 'UPDATE',
            'quantity' => 5,
            'remark' => 'Manual stock count',
        ]);
        $this->assertActivityLogged('order.cancelled', $shopAdmin->id, 'babybright', $cancelOrder, [
            'tenant_id' => 'babybright',
            'order_code' => 'BB-CANCEL',
            'remark' => 'Customer requested',
        ]);
        $this->assertActivityLogged('order.refunded', $shopAdmin->id, 'babybright', $refundOrder, [
            'tenant_id' => 'babybright',
            'order_code' => 'BB-REFUND',
            'reference' => 'BANK-REF-1',
            'note' => 'Refunded by bank',
        ]);
        $this->assertActivityLogged('settings.saved', $shopAdmin->id, 'babybright', Tenant::query()->findOrFail('babybright'), [
            'tenant_id' => 'babybright',
            'enable_shop' => false,
            'enable_coupon' => true,
        ]);
        $this->assertActivityLogged('banners.homepage.added', $shopAdmin->id, 'babybright', Tenant::query()->findOrFail('babybright'), [
            'tenant_id' => 'babybright',
            'column' => 'homepage_banners',
            'added_count' => 1,
        ]);
        $this->assertActivityLogged('banners.homepage.removed', $shopAdmin->id, 'babybright', Tenant::query()->findOrFail('babybright'), [
            'tenant_id' => 'babybright',
            'column' => 'homepage_banners',
            'removed_index' => 0,
        ]);

        $super = $this->createSuperAdmin('super2@example.com');
        $this->actingAs($super, 'admin');
        $this->withSession(['admin_current_tenant_id' => 'babybright']);

        Livewire::test(BrandsPage::class)
            ->set('createId', 'new-brand')
            ->set('createName', 'New Brand')
            ->set('createStatus', 'active')
            ->set('createDomain', 'new-brand.shopshop.test')
            ->call('createTenant')
            ->assertHasNoErrors()
            ->call('edit', 'new-brand')
            ->set('name', 'New Brand Updated')
            ->call('save')
            ->assertHasNoErrors();

        $newBrand = Tenant::query()->findOrFail('new-brand');

        $this->assertActivityLogged('brand.created', $super->id, 'babybright', $newBrand, [
            'tenant_id' => 'new-brand',
            'name' => 'New Brand',
            'domain' => 'new-brand.shopshop.test',
        ]);
        $this->assertActivityLogged('brand.updated', $super->id, 'babybright', $newBrand, [
            'tenant_id' => 'new-brand',
            'name' => 'New Brand Updated',
        ]);
    }

    public function test_logger_failure_does_not_break_primary_action(): void
    {
        $this->createTenant('babybright', 'Baby Bright');
        $admin = $this->createShopAdmin('babybright', 'shop@example.com');

        $this->app->bind(AdminActivityLogger::class, function (): AdminActivityLogger {
            return new class(app(AdminTenantScope::class)) extends AdminActivityLogger
            {
                protected function write(array $payload): void
                {
                    throw new RuntimeException('Forced logger failure.');
                }
            };
        });

        $this->actingAs($admin, 'admin');
        $this->withSession(['admin_current_tenant_id' => 'babybright']);

        Livewire::test(MyAccountPage::class)
            ->set('currentPassword', 'password')
            ->set('newPassword', 'NewSecret123!')
            ->set('newPasswordConfirmation', 'NewSecret123!')
            ->call('changePassword')
            ->assertHasNoErrors()
            ->assertSet('successMessage', 'Your password has been changed.');

        $this->assertTrue(Hash::check('NewSecret123!', $admin->refresh()->password));
    }

    public function test_super_admin_can_view_filter_and_paginate_activity_logs_but_shop_admin_cannot_access_page(): void
    {
        $this->createTenant('babybright', 'Baby Bright');
        $super = $this->createSuperAdmin();
        $shopAdmin = $this->createShopAdmin('babybright', 'shop@example.com');

        foreach (range(1, 16) as $index) {
            AdminActivityLog::query()->create([
                'admin_id' => $index % 2 === 0 ? $super->id : $shopAdmin->id,
                'tenant_id' => 'babybright',
                'action' => $index === 16 ? 'settings.saved' : 'product.stock.adjusted',
                'subject_type' => Tenant::class,
                'subject_id' => 'babybright',
                'detail' => ['index' => $index],
                'created_at' => "2026-07-{$index} 10:00:00",
            ]);
        }

        $this->actingAs($shopAdmin, 'admin');

        $this->get('http://admin.shopshop.test/admin')
            ->assertOk()
            ->assertDontSee('Admin audit log');
        $this->get('http://admin.shopshop.test/admin/activity-logs')
            ->assertForbidden();

        $this->actingAs($super, 'admin');
        $this->withSession(['admin_current_tenant_id' => 'babybright']);

        $this->get('http://admin.shopshop.test/admin')
            ->assertOk()
            ->assertSee('Admin audit log');
        $this->get('http://admin.shopshop.test/admin/activity-logs')
            ->assertOk()
            ->assertSee('Admin audit log');

        Livewire::test(AdminActivityLogsPage::class)
            ->assertViewHas('activityLogs', fn ($logs): bool => $logs->count() === 15
                && $logs->contains('action', 'settings.saved')
                && ! $logs->contains(fn ($log): bool => ($log->detail['index'] ?? null) === 1))
            ->assertSee('settings.saved')
            ->assertSee('product.stock.adjusted')
            ->set('action', 'settings.saved')
            ->assertViewHas('activityLogs', fn ($logs): bool => $logs->count() === 1
                && $logs->every(fn ($log): bool => $log->action === 'settings.saved'))
            ->set('action', '')
            ->set('adminId', (string) $shopAdmin->id)
            ->assertViewHas('activityLogs', fn ($logs): bool => $logs->count() === 8
                && $logs->every(fn ($log): bool => $log->admin_id === $shopAdmin->id))
            ->set('adminId', '')
            ->set('dateFrom', '2026-07-16')
            ->assertViewHas('activityLogs', fn ($logs): bool => $logs->count() === 1
                && $logs->first()->action === 'settings.saved');
    }

    private function assertActivityLogged(
        string $action,
        int $adminId,
        ?string $tenantId,
        mixed $subject = null,
        array $detailSubset = []
    ): void {
        $query = AdminActivityLog::query()
            ->where('action', $action)
            ->where('admin_id', $adminId);

        $tenantId === null
            ? $query->whereNull('tenant_id')
            : $query->where('tenant_id', $tenantId);

        if ($subject) {
            $query->where('subject_type', $subject::class)
                ->where('subject_id', (string) $subject->getKey());
        }

        $this->assertSame(1, $query->count(), "Expected exactly one {$action} activity log.");

        $detail = $query->firstOrFail()->detail ?? [];

        foreach ($detailSubset as $key => $value) {
            $this->assertSame($value, $detail[$key] ?? null, "Unexpected detail value for {$action}.{$key}");
        }
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

    private function createSuperAdmin(string $email = 'super@example.com'): Admin
    {
        return Admin::query()->create([
            'name' => 'Super Admin',
            'email' => $email,
            'password' => Hash::make('password'),
            'role' => 'super',
            'tenant_id' => null,
            'status' => 'active',
        ]);
    }

    private function createShopAdmin(string $tenantId, string $email): Admin
    {
        return Admin::query()->create([
            'name' => 'Shop Admin',
            'email' => $email,
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

    private function createProduct(string $tenantId, string $name, string $sku, int $availableQuantity = 10): ShopProduct
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
            'available_quantity' => $availableQuantity,
            'sort_no' => 1,
            'status' => 'active',
        ]);
    }

    private function createOrder(string $tenantId, string $orderCode, string $paymentStatus): ShopOrder
    {
        $user = $this->createUser($tenantId);

        return ShopOrder::query()->create([
            'tenant_id' => $tenantId,
            'id' => $tenantId.'-'.$orderCode,
            'user_id' => $user->id,
            'order_amount' => 198000,
            'shipping_amount' => 10000,
            'coupon_amount' => 0,
            'payment_amount' => 208000,
            'payment_uuid' => 'pay-'.$tenantId.'-'.$orderCode,
            'payment_status' => $paymentStatus,
            'payment_channel' => 'bcel',
            'shipping_fee_type' => 'prepaid',
            'shipping_channel' => 'hal',
            'shipping_channel_name' => 'HAL',
            'shipping_name' => 'Customer Name',
            'shipping_phone' => '205551111',
            'shipping_province' => 'VT',
            'shipping_district' => 'Vientiane',
            'shipping_village' => 'Nongbone',
            'shipping_tracking_number' => null,
            'shipping_status' => 'pending',
            'shipping_detail' => [],
            'order_code' => $orderCode,
            'total_product_quantity' => 2,
            'total_shipping_quantity' => 1,
            'created_at' => '2026-07-01 10:00:00',
        ]);
    }

    private function createOrderDetail(ShopOrder $order, ShopProduct $product, int $quantity, int $price): ShopOrderDetail
    {
        return ShopOrderDetail::query()->create([
            'tenant_id' => $order->tenant_id,
            'shop_order_id' => $order->id,
            'shop_product_id' => $product->id,
            'quantity' => $quantity,
            'price' => $price,
            'name' => $product->name,
            'images' => $product->images,
        ]);
    }
}

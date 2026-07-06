<?php

namespace Tests\Feature;

use App\Livewire\Admin\OrdersPage;
use App\Models\Admin;
use App\Models\ShopCoupon;
use App\Models\ShopOrder;
use App\Models\ShopOrderCoupon;
use App\Models\ShopOrderDetail;
use App\Models\ShopOrderLog;
use App\Models\ShopOrderPayment;
use App\Models\ShopProduct;
use App\Models\ShopProductStock;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class AdminM4Test extends TestCase
{
    use RefreshDatabase;

    public function test_orders_list_is_scoped_filterable_searchable_and_paginated(): void
    {
        $this->createTenant('babybright', 'Baby Bright');
        $this->createTenant('gadzila', 'Gadzila');
        $admin = $this->createShopAdmin('babybright');

        $this->createOrder('babybright', 'BB-1001', '2026-07-01 10:00:00', [
            'payment_status' => 'paid',
            'shipping_status' => 'pending',
            'shipping_phone' => '205551001',
        ]);
        $this->createOrder('babybright', 'BB-1002', '2026-07-02 10:00:00', [
            'payment_status' => 'pending',
            'shipping_status' => 'shipping',
            'shipping_phone' => '205551002',
        ]);
        $this->createOrder('gadzila', 'GZ-1001', '2026-07-01 10:00:00', [
            'payment_status' => 'paid',
            'shipping_status' => 'pending',
            'shipping_phone' => '205559999',
        ]);

        $this->actingAs($admin, 'admin');
        $this->withSession(['admin_current_tenant_id' => 'babybright']);

        Livewire::test(OrdersPage::class)
            ->assertSee('BB-1001')
            ->assertSee('BB-1002')
            ->assertDontSee('GZ-1001')
            ->set('paymentStatusFilter', 'paid')
            ->assertSee('BB-1001')
            ->assertDontSee('BB-1002')
            ->set('shippingStatusFilter', 'pending')
            ->assertSee('BB-1001')
            ->set('search', '205551002')
            ->assertDontSee('BB-1001')
            ->set('paymentStatusFilter', '')
            ->set('shippingStatusFilter', 'shipping')
            ->assertSee('BB-1002')
            ->set('dateFrom', '2026-07-03')
            ->assertDontSee('BB-1002');
    }

    public function test_order_detail_is_read_only_and_shipping_can_be_updated(): void
    {
        $this->createTenant('babybright', 'Baby Bright');
        $admin = $this->createShopAdmin('babybright');
        $product = $this->createProduct('babybright', 'Glow Cream', 'BB-GLOW');
        $order = $this->createOrder('babybright', 'BB-2001');
        $this->createOrderDetail($order, $product, 2, 99000);
        $this->createPayment($order, 'BCEL-REF-1');

        $this->actingAs($admin, 'admin');
        $this->withSession(['admin_current_tenant_id' => 'babybright']);

        Livewire::test(OrdersPage::class)
            ->call('selectOrder', $order->id)
            ->assertSee('Glow Cream')
            ->assertSee('198,000.00')
            ->assertSee('BCEL-REF-1')
            ->assertSee('Vientiane')
            ->set('shippingStatus', 'shipping')
            ->set('shippingTrackingNumber', 'HAL123456')
            ->call('updateShipping')
            ->assertHasNoErrors();

        $order->refresh();

        $this->assertSame('shipping', $order->shipping_status);
        $this->assertSame('HAL123456', $order->shipping_tracking_number);
    }

    public function test_resend_invoice_webhook_uses_signed_invoice_payload_and_logs_response(): void
    {
        $this->writeTestKeypair();
        Http::fake([
            'https://invoice.example.test/*' => Http::response(['ok' => true], 200),
        ]);

        $tenant = $this->createTenant('babybright', 'Baby Bright', [
            'delivery_contact_phone' => '856205551111',
            'order_invoice_webhook_url' => 'https://invoice.example.test/orders',
        ]);
        $admin = $this->createShopAdmin('babybright');
        $product = $this->createProduct('babybright', 'Glow Cream', 'BB-GLOW');
        $order = $this->createOrder('babybright', 'BB-3001', tenant: $tenant);
        $this->createOrderDetail($order, $product, 2, 99000);

        $this->actingAs($admin, 'admin');
        $this->withSession(['admin_current_tenant_id' => 'babybright']);

        Livewire::test(OrdersPage::class)
            ->call('selectOrder', $order->id)
            ->call('resendInvoiceWebhook')
            ->assertHasNoErrors();

        Http::assertSent(fn ($request): bool => $request->url() === 'https://invoice.example.test/orders'
            && $request['order_id'] === $order->id
            && $request['order_code'] === 'BB-3001'
            && $request['signature'] !== null
            && $request['order_details'][0]['product_name'] === 'Glow Cream'
        );

        $this->assertNotNull($order->refresh()->notified_invoice_api_at);
        $this->assertDatabaseHas('shop_order_logs', [
            'tenant_id' => 'babybright',
            'shop_order_id' => $order->id,
            'type' => 'notify_invoice_api',
        ]);
    }

    public function test_cancel_order_restocks_returns_coupon_and_logs(): void
    {
        $this->createTenant('babybright', 'Baby Bright');
        $admin = $this->createShopAdmin('babybright');
        $product = $this->createProduct('babybright', 'Glow Cream', 'BB-GLOW', availableQuantity: 1);
        $order = $this->createOrder('babybright', 'BB-4001', paymentStatus: 'pending');
        $this->createOrderDetail($order, $product, 3, 99000);
        $coupon = $this->createCoupon('babybright', availableQuantity: 0);
        $this->createOrderCoupon($order, $coupon);

        $this->actingAs($admin, 'admin');
        $this->withSession(['admin_current_tenant_id' => 'babybright']);

        Livewire::test(OrdersPage::class)
            ->call('selectOrder', $order->id)
            ->set('cancelRemark', 'Customer requested cancellation')
            ->call('cancelOrder')
            ->assertHasNoErrors();

        $this->assertSame('cancelled', $order->refresh()->payment_status);
        $this->assertSame(4, $product->refresh()->available_quantity);
        $this->assertSame(1, $coupon->refresh()->available_quantity);
        $this->assertDatabaseHas('shop_product_stocks', [
            'tenant_id' => 'babybright',
            'shop_order_id' => null,
            'shop_product_id' => $product->id,
            'quantity' => 3,
            'remark' => 'Order cancelled: Customer requested cancellation',
        ]);
        $this->assertDatabaseHas('shop_order_logs', [
            'tenant_id' => 'babybright',
            'shop_order_id' => $order->id,
            'type' => 'cancel_order',
        ]);
    }

    public function test_refund_sets_refunded_status_and_writes_reference_log(): void
    {
        $this->createTenant('babybright', 'Baby Bright');
        $admin = $this->createShopAdmin('babybright');
        $order = $this->createOrder('babybright', 'BB-5001', paymentStatus: 'paid');

        $this->actingAs($admin, 'admin');
        $this->withSession(['admin_current_tenant_id' => 'babybright']);

        Livewire::test(OrdersPage::class)
            ->call('selectOrder', $order->id)
            ->set('refundReference', 'BANK-REF-123')
            ->set('refundNote', 'Refund confirmed by bank CSV')
            ->call('refundOrder')
            ->assertHasNoErrors();

        $this->assertSame('refunded', $order->refresh()->payment_status);

        $log = ShopOrderLog::query()
            ->where('shop_order_id', $order->id)
            ->where('type', 'refund')
            ->firstOrFail();

        $this->assertSame('BANK-REF-123', $log->detail['reference']);
        $this->assertSame('Refund confirmed by bank CSV', $log->detail['note']);
        $this->assertSame($admin->id, $log->detail['actor']['id']);
        $this->assertArrayHasKey('recorded_at', $log->detail);
    }

    public function test_shop_admin_cannot_touch_another_shops_order(): void
    {
        $this->createTenant('babybright', 'Baby Bright');
        $this->createTenant('gadzila', 'Gadzila');
        $admin = $this->createShopAdmin('babybright');
        $otherOrder = $this->createOrder('gadzila', 'GZ-9001');

        $this->actingAs($admin, 'admin');
        $this->withSession(['admin_current_tenant_id' => 'babybright']);

        Livewire::test(OrdersPage::class)
            ->call('selectOrder', $otherOrder->id)
            ->assertStatus(404);
    }

    private function createTenant(string $id, string $name, array $attributes = []): Tenant
    {
        return Tenant::query()->create(array_merge([
            'id' => $id,
            'name' => $name,
            'status' => 'active',
            'enable_shop' => true,
            'enable_coupon' => false,
        ], $attributes));
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

    private function createProduct(
        string $tenantId,
        string $name,
        string $sku,
        int $availableQuantity = 10
    ): ShopProduct {
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

    private function createOrder(
        string $tenantId,
        string $orderCode,
        string $createdAt = '2026-07-01 10:00:00',
        array $attributes = [],
        ?Tenant $tenant = null,
        string $paymentStatus = 'paid'
    ): ShopOrder {
        $tenant ??= Tenant::query()->findOrFail($tenantId);
        $user = $this->createUser($tenant->id);

        return ShopOrder::query()->create(array_merge([
            'tenant_id' => $tenant->id,
            'id' => $tenant->id.'-'.$orderCode,
            'user_id' => $user->id,
            'order_amount' => 198000,
            'shipping_amount' => 10000,
            'coupon_amount' => 0,
            'payment_amount' => 208000,
            'payment_uuid' => 'pay-'.$tenant->id.'-'.$orderCode,
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
            'shipping_detail' => [
                'pre_order' => [
                    'end_branch' => ['tel' => '205552222'],
                ],
            ],
            'order_code' => $orderCode,
            'total_product_quantity' => 2,
            'total_shipping_quantity' => 1,
            'created_at' => $createdAt,
        ], $attributes));
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

    private function createPayment(ShopOrder $order, string $reference): ShopOrderPayment
    {
        return ShopOrderPayment::query()->create([
            'tenant_id' => $order->tenant_id,
            'shop_order_id' => $order->id,
            'channel' => 'bcel',
            'merchant_provider' => 'bcel',
            'merchant_id' => 'merchant-1',
            'amount' => $order->payment_amount,
            'ref' => $reference,
            'type' => 'payment',
        ]);
    }

    private function createCoupon(string $tenantId, int $availableQuantity): ShopCoupon
    {
        return ShopCoupon::query()->create([
            'tenant_id' => $tenantId,
            'status' => 'active',
            'code' => 'SAVE10',
            'type' => 'fixed',
            'amount' => 10000,
            'total_quantity' => 10,
            'available_quantity' => $availableQuantity,
            'user_daily_limit' => 1,
            'minimum_order_amount' => 0,
        ]);
    }

    private function createOrderCoupon(ShopOrder $order, ShopCoupon $coupon): ShopOrderCoupon
    {
        return ShopOrderCoupon::query()->create([
            'tenant_id' => $order->tenant_id,
            'shop_order_id' => $order->id,
            'shop_coupon_id' => $coupon->id,
            'user_id' => $order->user_id,
            'coupon_code' => $coupon->code,
            'coupon_type' => $coupon->type,
            'coupon_amount' => $coupon->amount,
            'discount_amount' => 10000,
            'before_discount_amount' => 198000,
            'user_daily_limit' => $coupon->user_daily_limit,
            'minimum_order_amount' => $coupon->minimum_order_amount,
        ]);
    }

    private function writeTestKeypair(): void
    {
        $keypairPath = storage_path('app/keypairs');

        if (! is_dir($keypairPath)) {
            mkdir($keypairPath, 0777, true);
        }

        $privateKey = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        openssl_pkey_export($privateKey, $privatePem);
        $publicPem = openssl_pkey_get_details($privateKey)['key'];

        file_put_contents($keypairPath.'/'.config('custom.private_key_name'), $privatePem);
        file_put_contents($keypairPath.'/'.config('custom.public_key_name'), $publicPem);
    }
}

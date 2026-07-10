<?php

namespace Tests\Feature;

use App\Models\ShippingLog;
use App\Models\ShopOrder;
use App\Models\ShopOrderLog;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HalWebhookHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_hal_post_with_wrong_signature_returns_403_and_does_not_update_order(): void
    {
        config(['custom.hal_sign_secret' => 'test-hal-secret']);

        $this->createTenant('babybright', 'Baby Bright');
        $order = $this->createOrder('babybright', 'BB-HAL-1', [
            'shipping_tracking_number' => 'HAL123',
            'shipping_status' => 'pending',
        ]);
        $payload = $this->halPayload('HAL123', 3);

        $this->postJson('/api/webhooks/hal', $payload, ['signature' => 'wrong-signature'])
            ->assertForbidden()
            ->assertSee('Signature mismatch');

        $this->assertSame('pending', $order->refresh()->shipping_status);
        $this->assertSame(0, ShopOrderLog::query()->where('shop_order_id', $order->id)->count());

        $shippingLog = ShippingLog::query()->latest('id')->firstOrFail();

        $this->assertSame('HAL Webhook Signature mismatch', $shippingLog->data['response']['message']);
        $this->assertSame('HAL123', $shippingLog->provider_reference);
    }

    public function test_hal_post_with_valid_signature_and_unknown_tracking_number_returns_cleanly(): void
    {
        config(['custom.hal_sign_secret' => 'test-hal-secret']);

        $payload = $this->halPayload('HAL404', 2);
        $signature = hash_hmac('sha256', json_encode($payload), 'test-hal-secret');

        $this->postJson('/api/webhooks/hal', $payload, ['signature' => $signature])
            ->assertOk()
            ->assertJson(['message' => 'Order not found for update shipping status']);

        $shippingLog = ShippingLog::query()->latest('id')->firstOrFail();

        $this->assertSame('Order not found for update shipping status', $shippingLog->data['response']['message']);
        $this->assertSame('HAL404', $shippingLog->provider_reference);
    }

    private function halPayload(string $shipmentNumber, int $statusId): array
    {
        return [
            'changes' => [
                'action' => 'updated',
                'shipment' => [
                    'shipment_number' => $shipmentNumber,
                    'shipment_status_id' => $statusId,
                ],
            ],
        ];
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

    private function createOrder(string $tenantId, string $orderCode, array $attributes = []): ShopOrder
    {
        $user = $this->createUser($tenantId);

        return ShopOrder::query()->create(array_merge([
            'tenant_id' => $tenantId,
            'id' => $tenantId.'-'.$orderCode,
            'user_id' => $user->id,
            'order_amount' => 198000,
            'shipping_amount' => 10000,
            'coupon_amount' => 0,
            'payment_amount' => 208000,
            'payment_uuid' => 'pay-'.$tenantId.'-'.$orderCode,
            'payment_status' => 'paid',
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
        ], $attributes));
    }
}

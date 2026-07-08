<?php

namespace Tests\Feature;

use App\Livewire\Admin\LogsPage;
use App\Models\Admin;
use App\Models\OtpLog;
use App\Models\ShippingLog;
use App\Models\Tenant;
use App\Models\WebhookLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class AdminM11Test extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_list_webhook_logs_newest_first_and_paginated(): void
    {
        $this->createTenant('babybright', 'Baby Bright');
        $super = $this->createSuperAdmin();

        foreach (range(1, 11) as $index) {
            WebhookLog::query()->create([
                'type' => 'payment',
                'message' => match ($index) {
                    1 => 'Oldest webhook row',
                    11 => 'Newest webhook row',
                    default => "Webhook middle {$index}",
                },
                'detail' => ['index' => $index],
                'model' => 'ShopOrder',
                'model_id' => (string) $index,
                'created_at' => "2026-07-{$index} 10:00:00",
            ]);
        }

        $this->actingAs($super, 'admin');
        $this->withSession(['admin_current_tenant_id' => 'babybright']);

        Livewire::test(LogsPage::class)
            ->assertSee('Newest webhook row')
            ->assertSee('Webhook middle 2')
            ->assertDontSee('Oldest webhook row');
    }

    public function test_super_admin_can_filter_and_search_webhook_logs(): void
    {
        $this->createTenant('babybright', 'Baby Bright');
        $super = $this->createSuperAdmin();
        WebhookLog::query()->create([
            'type' => 'payment',
            'message' => 'BCEL paid successfully',
            'detail' => ['status' => 'paid'],
            'model' => 'ShopOrder',
            'model_id' => 'A1',
            'created_at' => '2026-07-03 10:00:00',
        ]);
        WebhookLog::query()->create([
            'type' => 'refund',
            'message' => 'Refund ignored',
            'detail' => ['status' => 'ignored'],
            'model' => 'OtherModel',
            'model_id' => 'B1',
            'created_at' => '2026-07-05 10:00:00',
        ]);

        $this->actingAs($super, 'admin');
        $this->withSession(['admin_current_tenant_id' => 'babybright']);

        Livewire::test(LogsPage::class)
            ->set('search', 'ShopOrder')
            ->assertSee('BCEL paid successfully')
            ->assertDontSee('Refund ignored')
            ->set('search', '')
            ->set('dateFrom', '2026-07-04')
            ->assertDontSee('BCEL paid successfully')
            ->assertSee('Refund ignored');
    }

    public function test_super_admin_can_filter_and_search_shipping_logs(): void
    {
        $this->createTenant('babybright', 'Baby Bright');
        $super = $this->createSuperAdmin();
        ShippingLog::query()->create([
            'provider' => 'hal',
            'provider_reference' => 'HAL-TRACK-1',
            'type' => 'webhook_post',
            'data' => ['response' => ['message' => 'Delivered']],
            'created_at' => '2026-07-03 10:00:00',
        ]);
        ShippingLog::query()->create([
            'provider' => 'seller',
            'provider_reference' => 'SELLER-TRACK-2',
            'type' => 'manual_update',
            'data' => ['response' => ['message' => 'Manual']],
            'created_at' => '2026-07-05 10:00:00',
        ]);

        $this->actingAs($super, 'admin');
        $this->withSession(['admin_current_tenant_id' => 'babybright']);

        Livewire::test(LogsPage::class)
            ->set('logType', 'shipping')
            ->set('search', 'HAL-TRACK-1')
            ->assertSee('HAL-TRACK-1')
            ->assertDontSee('SELLER-TRACK-2')
            ->set('search', '')
            ->set('dateFrom', '2026-07-04')
            ->assertDontSee('HAL-TRACK-1')
            ->assertSee('SELLER-TRACK-2');
    }

    public function test_super_admin_can_filter_and_search_otp_logs_without_rendering_raw_otp(): void
    {
        $this->createTenant('babybright', 'Baby Bright');
        $super = $this->createSuperAdmin();
        OtpLog::query()->create([
            'provider' => 'telbiz',
            'provider_reference' => 'OTP-REF-1',
            'msisdn' => '205551111',
            'otp' => '123456',
            'data' => ['message' => 'OTP sent', 'otp' => '123456'],
            'created_at' => '2026-07-03 10:00:00',
        ]);
        OtpLog::query()->create([
            'provider' => 'ltc',
            'provider_reference' => 'OTP-REF-2',
            'msisdn' => '205552222',
            'otp' => '654321',
            'data' => ['message' => 'LTC OTP sent'],
            'created_at' => '2026-07-05 10:00:00',
        ]);

        $this->actingAs($super, 'admin');
        $this->withSession(['admin_current_tenant_id' => 'babybright']);

        Livewire::test(LogsPage::class)
            ->set('logType', 'otp')
            ->assertSee('205551111')
            ->assertSee('***')
            ->assertDontSee('123456')
            ->set('search', 'OTP-REF-2')
            ->assertSee('OTP-REF-2')
            ->assertDontSee('OTP-REF-1')
            ->assertDontSee('654321')
            ->set('search', '')
            ->set('dateTo', '2026-07-04')
            ->assertSee('OTP-REF-1')
            ->assertDontSee('OTP-REF-2')
            ->assertDontSee('123456');
    }

    public function test_logs_route_is_super_only_and_hidden_from_shop_admin_nav(): void
    {
        $this->createTenant('babybright', 'Baby Bright');
        $shopAdmin = $this->createShopAdmin('babybright');
        $super = $this->createSuperAdmin();

        $this->actingAs($shopAdmin, 'admin');

        $this->get('http://admin.shopshop.test/admin')
            ->assertOk()
            ->assertDontSee('Logs');
        $this->get('http://admin.shopshop.test/admin/logs')
            ->assertForbidden();

        $this->actingAs($super, 'admin');
        $this->withSession(['admin_current_tenant_id' => 'babybright']);

        $this->get('http://admin.shopshop.test/admin')
            ->assertOk()
            ->assertSee('Logs');
        $this->get('http://admin.shopshop.test/admin/logs')
            ->assertOk()
            ->assertSee('Logs');
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

<?php

namespace Tests\Feature;

use App\Livewire\Admin\ShippingRulesPage;
use App\Models\Admin;
use App\Models\ShopShippingRule;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class AdminM7Test extends TestCase
{
    use RefreshDatabase;

    private const OVERLAP_TRIGGER_MESSAGE = 'Active time range overlaps an existing active record for this tenant';

    public function test_shipping_rule_list_is_scoped_and_filtered(): void
    {
        $this->createTenant('babybright', 'Baby Bright');
        $this->createTenant('gadzila', 'Gadzila');
        $admin = $this->createShopAdmin('babybright');

        $this->createShippingRule('babybright', 'active', '2026-07-01 00:00:00', '2026-07-10 00:00:00', 'cod', '2-3 days');
        $this->createShippingRule('babybright', 'inactive', '2026-07-10 00:00:00', '2026-07-20 00:00:00', 'free', '4-5 days');
        $this->createShippingRule('gadzila', 'active', '2026-07-01 00:00:00', '2026-07-10 00:00:00', 'prepaid', 'Hidden shop');

        $this->actingAs($admin, 'admin');
        $this->withSession(['admin_current_tenant_id' => 'babybright']);

        Livewire::test(ShippingRulesPage::class)
            ->assertSee('2-3 days')
            ->assertSee('4-5 days')
            ->assertDontSee('Hidden shop')
            ->set('statusFilter', 'inactive')
            ->assertDontSee('2-3 days')
            ->assertSee('4-5 days');
    }

    public function test_admin_can_create_edit_and_delete_shipping_rule(): void
    {
        $this->createTenant('babybright', 'Baby Bright');
        $admin = $this->createShopAdmin('babybright');

        $this->actingAs($admin, 'admin');
        $this->withSession(['admin_current_tenant_id' => 'babybright']);

        Livewire::test(ShippingRulesPage::class)
            ->call('create')
            ->set('status', 'active')
            ->set('startedAt', '2026-07-01T00:00')
            ->set('endedAt', '2026-07-10T00:00')
            ->set('minimumAmount', '50000')
            ->set('shippingFeeType', 'cod')
            ->set('shippingDaysText', '2-3 days')
            ->set('remark', 'Launch rule')
            ->call('save')
            ->assertHasNoErrors();

        $rule = ShopShippingRule::query()->firstOrFail();

        $this->assertSame('babybright', $rule->tenant_id);
        $this->assertSame('50000.00', $rule->minimum_amount);
        $this->assertSame('cod', $rule->shipping_fee_type);
        $this->assertSame('2026-07-01 00:00:00', $rule->started_at->format('Y-m-d H:i:s'));

        Livewire::test(ShippingRulesPage::class)
            ->call('edit', $rule->id)
            ->set('status', 'inactive')
            ->set('minimumAmount', '75000')
            ->set('shippingFeeType', 'free')
            ->set('shippingDaysText', 'Same day')
            ->set('remark', '')
            ->call('save')
            ->assertHasNoErrors();

        $rule->refresh();

        $this->assertSame('inactive', $rule->status);
        $this->assertSame('75000.00', $rule->minimum_amount);
        $this->assertSame('free', $rule->shipping_fee_type);
        $this->assertSame('Same day', $rule->shipping_days_text);
        $this->assertNull($rule->remark);

        Livewire::test(ShippingRulesPage::class)
            ->call('delete', $rule->id)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('shop_shipping_rules', [
            'id' => $rule->id,
            'tenant_id' => 'babybright',
        ]);
    }

    public function test_active_overlap_on_create_surfaces_exact_trigger_message_as_validation_error(): void
    {
        $this->createTenant('babybright', 'Baby Bright');
        $admin = $this->createShopAdmin('babybright');

        $this->createShippingRule('babybright', 'active', '2026-07-01 00:00:00', '2026-07-10 00:00:00');

        $this->actingAs($admin, 'admin');
        $this->withSession(['admin_current_tenant_id' => 'babybright']);

        Livewire::test(ShippingRulesPage::class)
            ->call('create')
            ->set('status', 'active')
            ->set('startedAt', '2026-07-05T00:00')
            ->set('endedAt', '2026-07-12T00:00')
            ->set('minimumAmount', '0')
            ->set('shippingFeeType', 'prepaid')
            ->set('shippingDaysText', '2-3 days')
            ->call('save')
            ->assertHasErrors(['startedAt'])
            ->assertSee(self::OVERLAP_TRIGGER_MESSAGE);
    }

    public function test_active_overlap_on_update_surfaces_exact_trigger_message_as_validation_error(): void
    {
        $this->createTenant('babybright', 'Baby Bright');
        $admin = $this->createShopAdmin('babybright');

        $this->createShippingRule('babybright', 'active', '2026-07-01 00:00:00', '2026-07-10 00:00:00');
        $rule = $this->createShippingRule('babybright', 'active', '2026-07-10 00:00:00', '2026-07-20 00:00:00');

        $this->actingAs($admin, 'admin');
        $this->withSession(['admin_current_tenant_id' => 'babybright']);

        Livewire::test(ShippingRulesPage::class)
            ->call('edit', $rule->id)
            ->set('startedAt', '2026-07-05T00:00')
            ->call('save')
            ->assertHasErrors(['startedAt'])
            ->assertSee(self::OVERLAP_TRIGGER_MESSAGE);
    }

    public function test_overlap_is_allowed_when_other_rule_is_inactive_or_different_tenant(): void
    {
        $this->createTenant('babybright', 'Baby Bright');
        $this->createTenant('gadzila', 'Gadzila');
        $admin = $this->createShopAdmin('babybright');

        $this->createShippingRule('babybright', 'inactive', '2026-07-01 00:00:00', '2026-07-10 00:00:00');
        $this->createShippingRule('gadzila', 'active', '2026-07-01 00:00:00', '2026-07-10 00:00:00');

        $this->actingAs($admin, 'admin');
        $this->withSession(['admin_current_tenant_id' => 'babybright']);

        Livewire::test(ShippingRulesPage::class)
            ->call('create')
            ->set('status', 'active')
            ->set('startedAt', '2026-07-05T00:00')
            ->set('endedAt', '2026-07-12T00:00')
            ->set('minimumAmount', '0')
            ->set('shippingFeeType', 'prepaid')
            ->set('shippingDaysText', '2-3 days')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('shop_shipping_rules', [
            'tenant_id' => 'babybright',
            'status' => 'active',
            'shipping_fee_type' => 'prepaid',
        ]);
    }

    public function test_shop_admin_cannot_edit_or_delete_another_shops_shipping_rule(): void
    {
        $this->createTenant('babybright', 'Baby Bright');
        $this->createTenant('gadzila', 'Gadzila');
        $admin = $this->createShopAdmin('babybright');
        $otherRule = $this->createShippingRule('gadzila', 'active', '2026-07-01 00:00:00', '2026-07-10 00:00:00');

        $this->actingAs($admin, 'admin');
        $this->withSession(['admin_current_tenant_id' => 'babybright']);

        Livewire::test(ShippingRulesPage::class)
            ->call('edit', $otherRule->id)
            ->assertStatus(404);

        Livewire::test(ShippingRulesPage::class)
            ->call('delete', $otherRule->id)
            ->assertStatus(404);
    }

    public function test_admin_layout_links_to_shipping_rules(): void
    {
        $this->createTenant('babybright', 'Baby Bright');
        $admin = $this->createShopAdmin('babybright');

        $this->actingAs($admin, 'admin');

        $this->get('http://admin.shopshop.test/admin')
            ->assertOk()
            ->assertSee('Shipping rules')
            ->assertSee(route('admin.shipping-rules'), false);
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

    private function createShippingRule(
        string $tenantId,
        string $status,
        string $startedAt,
        string $endedAt,
        string $shippingFeeType = 'cod',
        string $shippingDaysText = '2-3 days'
    ): ShopShippingRule {
        return ShopShippingRule::query()->create([
            'tenant_id' => $tenantId,
            'status' => $status,
            'started_at' => $startedAt,
            'ended_at' => $endedAt,
            'minimum_amount' => 0,
            'shipping_fee_type' => $shippingFeeType,
            'shipping_days_text' => $shippingDaysText,
            'remark' => 'Seeded rule',
        ]);
    }
}

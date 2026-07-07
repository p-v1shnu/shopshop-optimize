<?php

namespace Tests\Feature;

use App\Livewire\Admin\AdminAccountsPage;
use App\Livewire\Admin\LoginPage;
use App\Models\Admin;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class AdminM1Test extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_create_shop_admin_bound_to_one_tenant(): void
    {
        $this->createTenant('babybright', 'Baby Bright');
        $super = $this->createSuperAdmin();

        $this->actingAs($super, 'admin');

        Livewire::test(AdminAccountsPage::class)
            ->set('createName', 'Baby Bright Manager')
            ->set('createEmail', 'manager@babybright.example')
            ->set('createRole', 'shop')
            ->set('createTenantId', 'babybright')
            ->set('createPassword', 'Secret123!')
            ->call('createAdmin')
            ->assertHasNoErrors();

        $admin = Admin::query()->where('email', 'manager@babybright.example')->first();

        $this->assertNotNull($admin);
        $this->assertSame('Baby Bright Manager', $admin->name);
        $this->assertSame('shop', $admin->role);
        $this->assertSame('babybright', $admin->tenant_id);
        $this->assertSame('active', $admin->status);
        $this->assertTrue(Hash::check('Secret123!', $admin->password));
    }

    public function test_super_admin_can_create_super_admin_with_null_tenant(): void
    {
        $this->createTenant('babybright', 'Baby Bright');
        $super = $this->createSuperAdmin();

        $this->actingAs($super, 'admin');

        Livewire::test(AdminAccountsPage::class)
            ->set('createName', 'Platform Admin')
            ->set('createEmail', 'platform@example.com')
            ->set('createRole', 'super')
            ->set('createTenantId', 'babybright')
            ->set('createPassword', 'Secret123!')
            ->call('createAdmin')
            ->assertHasNoErrors();

        $admin = Admin::query()->where('email', 'platform@example.com')->first();

        $this->assertNotNull($admin);
        $this->assertSame('super', $admin->role);
        $this->assertNull($admin->tenant_id);
        $this->assertTrue(Hash::check('Secret123!', $admin->password));
    }

    public function test_super_admin_can_reset_admin_password(): void
    {
        $this->createTenant('babybright', 'Baby Bright');
        $super = $this->createSuperAdmin();
        $target = $this->createShopAdmin('babybright');

        $this->actingAs($super, 'admin');

        Livewire::test(AdminAccountsPage::class)
            ->set('resetAdminId', $target->id)
            ->set('resetPasswordValue', 'NewSecret123!')
            ->call('resetPassword')
            ->assertHasNoErrors();

        $target->refresh();

        $this->assertTrue(Hash::check('NewSecret123!', $target->password));
    }

    public function test_disabled_admin_cannot_log_in(): void
    {
        $this->createTenant('babybright', 'Baby Bright');
        $super = $this->createSuperAdmin();
        $target = $this->createShopAdmin('babybright');

        $this->actingAs($super, 'admin');

        Livewire::test(AdminAccountsPage::class)
            ->call('setStatus', $target->id, 'inactive')
            ->assertHasNoErrors();

        $target->refresh();
        $this->assertSame('inactive', $target->status);

        auth('admin')->logout();

        Livewire::test(LoginPage::class)
            ->set('email', $target->email)
            ->set('password', 'password')
            ->call('login')
            ->assertHasErrors(['email']);

        $this->assertGuest('admin');
    }

    public function test_super_admin_cannot_deactivate_their_own_account(): void
    {
        $this->createTenant('babybright', 'Baby Bright');
        $super = $this->createSuperAdmin();

        $this->actingAs($super, 'admin');

        Livewire::test(AdminAccountsPage::class)
            ->call('setStatus', $super->id, 'inactive')
            ->assertHasErrors(['status']);

        $this->assertSame('active', $super->refresh()->status);
    }

    public function test_cannot_deactivate_last_active_super_admin(): void
    {
        $this->createTenant('babybright', 'Baby Bright');
        $inactiveActor = Admin::query()->create([
            'name' => 'Inactive Super',
            'email' => 'inactive-super@example.com',
            'password' => Hash::make('password'),
            'role' => 'super',
            'tenant_id' => null,
            'status' => 'inactive',
        ]);
        $lastActiveSuper = $this->createSuperAdmin();

        $this->actingAs($inactiveActor, 'admin');

        Livewire::test(AdminAccountsPage::class)
            ->call('setStatus', $lastActiveSuper->id, 'inactive')
            ->assertHasErrors(['status']);

        $this->assertSame('active', $lastActiveSuper->refresh()->status);
    }

    public function test_super_admin_can_deactivate_non_last_super_admin(): void
    {
        $this->createTenant('babybright', 'Baby Bright');
        $actor = $this->createSuperAdmin();
        $otherSuper = Admin::query()->create([
            'name' => 'Other Super',
            'email' => 'other-super@example.com',
            'password' => Hash::make('password'),
            'role' => 'super',
            'tenant_id' => null,
            'status' => 'active',
        ]);

        $this->actingAs($actor, 'admin');

        Livewire::test(AdminAccountsPage::class)
            ->call('setStatus', $otherSuper->id, 'inactive')
            ->assertHasNoErrors();

        $this->assertSame('inactive', $otherSuper->refresh()->status);
        $this->assertSame('active', $actor->refresh()->status);
    }

    public function test_shop_admin_cannot_access_admin_accounts_routes_or_nav(): void
    {
        $this->createTenant('babybright', 'Baby Bright');
        $shopAdmin = $this->createShopAdmin('babybright');

        $this->actingAs($shopAdmin, 'admin');

        $this->get('http://admin.shopshop.test/admin')
            ->assertOk()
            ->assertDontSee('Admin accounts');

        $this->get('http://admin.shopshop.test/admin/admin-accounts')
            ->assertForbidden();
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

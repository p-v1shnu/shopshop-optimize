<?php

namespace Tests\Feature;

use App\Livewire\Admin\MyAccountPage;
use App\Models\Admin;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class AdminMyAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_change_their_own_password(): void
    {
        $this->createTenant('babybright', 'Baby Bright');
        $admin = $this->createShopAdmin('babybright');

        $this->actingAs($admin, 'admin');
        $this->withSession(['admin_current_tenant_id' => 'babybright']);

        Livewire::test(MyAccountPage::class)
            ->set('currentPassword', 'password')
            ->set('newPassword', 'NewPassword123!')
            ->set('newPasswordConfirmation', 'NewPassword123!')
            ->call('changePassword')
            ->assertHasNoErrors()
            ->assertSee('Your password has been changed.');

        $admin->refresh();

        $this->assertTrue(Hash::check('NewPassword123!', $admin->password));
        $this->assertFalse(Hash::check('password', $admin->password));
    }

    public function test_change_password_requires_correct_current_password(): void
    {
        $this->createTenant('babybright', 'Baby Bright');
        $admin = $this->createShopAdmin('babybright');
        $originalHash = $admin->password;

        $this->actingAs($admin, 'admin');
        $this->withSession(['admin_current_tenant_id' => 'babybright']);

        Livewire::test(MyAccountPage::class)
            ->set('currentPassword', 'wrong-password')
            ->set('newPassword', 'NewPassword123!')
            ->set('newPasswordConfirmation', 'NewPassword123!')
            ->call('changePassword')
            ->assertHasErrors(['currentPassword']);

        $this->assertSame($originalHash, $admin->refresh()->password);
    }

    public function test_change_password_requires_matching_confirmation(): void
    {
        $this->createTenant('babybright', 'Baby Bright');
        $admin = $this->createShopAdmin('babybright');
        $originalHash = $admin->password;

        $this->actingAs($admin, 'admin');
        $this->withSession(['admin_current_tenant_id' => 'babybright']);

        Livewire::test(MyAccountPage::class)
            ->set('currentPassword', 'password')
            ->set('newPassword', 'NewPassword123!')
            ->set('newPasswordConfirmation', 'Different123!')
            ->call('changePassword')
            ->assertHasErrors(['newPasswordConfirmation']);

        $this->assertSame($originalHash, $admin->refresh()->password);
    }

    public function test_my_account_route_and_header_link_are_available_to_all_admin_roles(): void
    {
        $this->createTenant('babybright', 'Baby Bright');
        $shopAdmin = $this->createShopAdmin('babybright');
        $superAdmin = $this->createSuperAdmin();

        $this->actingAs($shopAdmin, 'admin');
        $this->get('http://admin.shopshop.test/admin')
            ->assertOk()
            ->assertSee('My account');
        $this->get('http://admin.shopshop.test/admin/my-account')
            ->assertOk()
            ->assertSee('My account');

        $this->actingAs($superAdmin, 'admin');
        $this->withSession(['admin_current_tenant_id' => 'babybright']);
        $this->get('http://admin.shopshop.test/admin')
            ->assertOk()
            ->assertSee('My account');
        $this->get('http://admin.shopshop.test/admin/my-account')
            ->assertOk()
            ->assertSee('My account');
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

<?php

namespace Tests\Feature;

use App\Livewire\Admin\ProductsPage;
use App\Models\Admin;
use App\Models\ShopProduct;
use App\Models\ShopProductStock;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class AdminM3Test extends TestCase
{
    use RefreshDatabase;

    public function test_product_list_is_scoped_searchable_status_filtered_and_ordered(): void
    {
        $this->createTenant('babybright', 'Baby Bright');
        $this->createTenant('gadzila', 'Gadzila');
        $admin = $this->createShopAdmin('babybright');

        $this->createProduct('babybright', 'Zinc Soap', 'BB-ZINC', 'active', 20);
        $this->createProduct('babybright', 'Aloe Gel', 'BB-ALOE', 'inactive', 10);
        $this->createProduct('gadzila', 'Zinc Other Shop', 'GZ-ZINC', 'active', 1);

        $this->actingAs($admin, 'admin');
        $this->withSession(['admin_current_tenant_id' => 'babybright']);

        Livewire::test(ProductsPage::class)
            ->assertSeeInOrder(['Aloe Gel', 'Zinc Soap'])
            ->assertDontSee('Zinc Other Shop')
            ->set('search', 'zinc')
            ->assertSee('Zinc Soap')
            ->assertDontSee('Aloe Gel')
            ->set('statusFilter', 'inactive')
            ->assertDontSee('Zinc Soap');
    }

    public function test_admin_can_create_product_with_uploaded_cover_image_and_public_url_shape(): void
    {
        Storage::fake('s3');
        config(['filesystems.default' => 's3']);

        $this->createTenant('babybright', 'Baby Bright');
        $admin = $this->createShopAdmin('babybright');

        $this->actingAs($admin, 'admin');
        $this->withSession(['admin_current_tenant_id' => 'babybright']);

        Livewire::test(ProductsPage::class)
            ->call('create')
            ->set('name', 'Glow Cream')
            ->set('normalPrice', '120000')
            ->set('price', '99000')
            ->set('sku', 'BB-GLOW')
            ->set('shortDescription', 'Daily glow cream')
            ->set('longDescription', 'A longer storefront description.')
            ->set('totalUnit', 50)
            ->set('unitType', 'ml')
            ->set('storage', 'Room temperature')
            ->set('sortNo', 3)
            ->set('status', 'active')
            ->set('uploadedImages', [
                UploadedFile::fake()->image('cover.jpg', 500, 500),
                UploadedFile::fake()->image('side.jpg', 500, 500),
            ])
            ->set('coverSelection', 'upload-0')
            ->call('save')
            ->assertHasNoErrors();

        $product = ShopProduct::query()->where('sku', 'BB-GLOW')->firstOrFail();

        $this->assertSame('babybright', $product->tenant_id);
        $this->assertCount(2, $product->images);
        $this->assertTrue($product->images[0]['is_cover']);
        $this->assertFalse($product->images[1]['is_cover']);
        $this->assertStringContainsString('/products/babybright/', $product->images[0]['filename']);

        $storedPath = ltrim(str_replace('/storage/', '', parse_url($product->images[0]['filename'], PHP_URL_PATH)), '/');
        Storage::disk('s3')->assertExists($storedPath);
        $this->assertSame($product->images[0]['filename'], $product->cover_image);
    }

    public function test_admin_can_edit_product_and_sku_is_unique_per_tenant(): void
    {
        $this->createTenant('babybright', 'Baby Bright');
        $this->createTenant('gadzila', 'Gadzila');
        $admin = $this->createShopAdmin('babybright');
        $product = $this->createProduct('babybright', 'Original Name', 'BB-ONE');
        $this->createProduct('babybright', 'Other Product', 'BB-TWO');
        $this->createProduct('gadzila', 'Other Tenant Product', 'BB-TWO');

        $this->actingAs($admin, 'admin');
        $this->withSession(['admin_current_tenant_id' => 'babybright']);

        Livewire::test(ProductsPage::class)
            ->call('edit', $product->id)
            ->set('name', 'Updated Name')
            ->set('sku', 'BB-TWO')
            ->call('save')
            ->assertHasErrors(['sku'])
            ->set('sku', 'GZ-ALLOWED')
            ->set('price', '15000')
            ->set('status', 'inactive')
            ->call('save')
            ->assertHasNoErrors();

        $product->refresh();

        $this->assertSame('Updated Name', $product->name);
        $this->assertSame('GZ-ALLOWED', $product->sku);
        $this->assertSame('inactive', $product->status);
        $this->assertSame('15000.00', $product->price);
    }

    public function test_stock_update_and_set_use_stored_procedure_and_write_ledger_rows(): void
    {
        $this->createTenant('babybright', 'Baby Bright');
        $admin = $this->createShopAdmin('babybright');
        $product = $this->createProduct('babybright', 'Stocked Product', 'BB-STOCK');

        $this->actingAs($admin, 'admin');
        $this->withSession(['admin_current_tenant_id' => 'babybright']);

        Livewire::test(ProductsPage::class)
            ->call('edit', $product->id)
            ->set('stockType', 'UPDATE')
            ->set('stockQuantity', 7)
            ->set('stockRemark', 'Initial stock')
            ->call('adjustStock')
            ->assertHasNoErrors()
            ->set('stockType', 'SET')
            ->set('stockQuantity', 3)
            ->set('stockRemark', 'Cycle count')
            ->call('adjustStock')
            ->assertHasNoErrors();

        $this->assertSame(3, $product->refresh()->available_quantity);
        $this->assertSame(
            [7, -4],
            ShopProductStock::query()
                ->where('shop_product_id', $product->id)
                ->orderBy('id')
                ->pluck('quantity')
                ->all()
        );
        $this->assertSame('Cycle count', ShopProductStock::query()->latest('id')->value('remark'));
    }

    public function test_shop_admin_cannot_access_another_shops_product(): void
    {
        $this->createTenant('babybright', 'Baby Bright');
        $this->createTenant('gadzila', 'Gadzila');
        $admin = $this->createShopAdmin('babybright');
        $otherProduct = $this->createProduct('gadzila', 'Hidden Product', 'GZ-HIDDEN');

        $this->actingAs($admin, 'admin');
        $this->withSession(['admin_current_tenant_id' => 'babybright']);

        Livewire::test(ProductsPage::class)
            ->call('edit', $otherProduct->id)
            ->assertStatus(404);
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

    private function createProduct(
        string $tenantId,
        string $name,
        string $sku,
        string $status = 'active',
        int $sortNo = 1
    ): ShopProduct {
        return ShopProduct::query()->create([
            'tenant_id' => $tenantId,
            'name' => $name,
            'images' => [
                ['filename' => "https://assets.example.test/{$sku}.jpg", 'is_cover' => true],
            ],
            'normal_price' => 20000,
            'price' => 10000,
            'short_description' => 'Short',
            'long_description' => 'Long',
            'sku' => $sku,
            'total_unit' => 1,
            'unit_type' => 'pc',
            'storage' => 'Room temperature',
            'sort_no' => $sortNo,
            'available_quantity' => 0,
            'status' => $status,
        ]);
    }
}

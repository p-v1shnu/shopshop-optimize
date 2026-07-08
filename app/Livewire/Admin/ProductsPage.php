<?php

namespace App\Livewire\Admin;

use App\Models\ShopProduct;
use App\Models\ShopProductStock;
use App\Support\AdminActivityLogger;
use App\Support\AdminTenantScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\Features\SupportFileUploads\WithFileUploads;
use Livewire\WithPagination;

class ProductsPage extends Component
{
    use WithFileUploads, WithPagination;

    public string $tenantId;

    public string $search = '';

    public string $statusFilter = '';

    public ?int $editingProductId = null;

    public bool $isEditing = false;

    public string $name = '';

    public ?string $normalPrice = null;

    public string $price = '';

    public string $sku = '';

    public ?string $shortDescription = null;

    public ?string $longDescription = null;

    public ?int $totalUnit = null;

    public ?string $unitType = null;

    public ?string $storage = null;

    public ?int $sortNo = null;

    public string $status = 'active';

    public array $existingImages = [];

    public array $uploadedImages = [];

    public ?string $coverSelection = null;

    public string $stockType = 'UPDATE';

    public int $stockQuantity = 0;

    public string $stockRemark = '';

    public function mount(AdminTenantScope $tenantScope): void
    {
        $admin = Auth::guard('admin')->user();
        $this->tenantId = $admin->isShop()
            ? $admin->tenant_id
            : (string) $tenantScope->currentTenantId();

        abort_if(blank($this->tenantId), 404);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        $this->resetForm();
        $this->isEditing = true;
    }

    public function edit(int $productId): void
    {
        $product = $this->findScopedProduct($productId);

        $this->editingProductId = $product->id;
        $this->isEditing = true;
        $this->name = $product->name;
        $this->normalPrice = $product->normal_price === null ? null : (string) $product->normal_price;
        $this->price = (string) $product->price;
        $this->sku = $product->sku;
        $this->shortDescription = $product->short_description;
        $this->longDescription = $product->long_description;
        $this->totalUnit = $product->total_unit;
        $this->unitType = $product->unit_type;
        $this->storage = $product->storage;
        $this->sortNo = $product->sort_no;
        $this->status = $product->status;
        $this->existingImages = collect($product->images ?? [])
            ->map(fn (array $image): array => [
                'filename' => $image['filename'],
                'is_cover' => (bool) ($image['is_cover'] ?? false),
            ])
            ->values()
            ->all();
        $this->uploadedImages = [];
        $this->coverSelection = $this->defaultCoverSelection($this->existingImages);
        $this->resetStockForm();
    }

    public function cancelEdit(): void
    {
        $this->resetForm();
    }

    public function save(): void
    {
        $validated = $this->validate($this->productRules());

        $images = $this->normalizedImages();

        $payload = [
            'tenant_id' => $this->tenantId,
            'name' => $validated['name'],
            'images' => $images,
            'normal_price' => $this->blankToNull($validated['normalPrice']),
            'price' => $validated['price'],
            'short_description' => $this->blankToNull($validated['shortDescription']),
            'long_description' => $this->blankToNull($validated['longDescription']),
            'sku' => $validated['sku'],
            'total_unit' => $validated['totalUnit'],
            'unit_type' => $this->blankToNull($validated['unitType']),
            'storage' => $this->blankToNull($validated['storage']),
            'sort_no' => $validated['sortNo'],
            'status' => $validated['status'],
        ];

        if ($this->editingProductId) {
            $this->findScopedProduct($this->editingProductId)->update($payload);
        } else {
            ShopProduct::query()->create($payload);
        }

        $this->resetForm();
    }

    public function adjustStock(): void
    {
        abort_if(! $this->editingProductId, 404);

        $validated = $this->validate([
            'stockType' => ['required', Rule::in(['UPDATE', 'SET'])],
            'stockQuantity' => [
                'required',
                'integer',
                Rule::when($this->stockType === 'SET', ['min:0']),
            ],
            'stockRemark' => ['required', 'string', 'max:1000'],
        ]);

        $product = $this->findScopedProduct($this->editingProductId);
        $result = $product->updateProductAvailableQuantity(
            (int) $validated['stockQuantity'],
            $validated['stockType'],
            $validated['stockRemark']
        );

        if (! $result['success']) {
            $this->addError('stockQuantity', $result['message']);
            return;
        }

        app(AdminActivityLogger::class)->log('product.stock.adjusted', $product, [
            'tenant_id' => $this->tenantId,
            'type' => $validated['stockType'],
            'quantity' => (int) $validated['stockQuantity'],
            'remark' => $validated['stockRemark'],
            'available_quantity' => $product->refresh()->available_quantity,
        ]);

        $this->resetStockForm();
    }

    public function render()
    {
        return view('admin.products-page', [
            'products' => $this->productsQuery()->paginate(10),
            'stockMovements' => $this->stockMovements(),
        ])->layout('admin.layout')
            ->title('Products');
    }

    private function productsQuery()
    {
        return ShopProduct::query()
            ->where('tenant_id', $this->tenantId)
            ->when($this->search !== '', function ($query): void {
                $search = '%'.$this->search.'%';
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', $search)
                        ->orWhere('sku', 'like', $search);
                });
            })
            ->when($this->statusFilter !== '', fn ($query) => $query->where('status', $this->statusFilter))
            ->orderByRaw('sort_no is null')
            ->orderBy('sort_no')
            ->orderBy('name');
    }

    private function productRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'normalPrice' => ['nullable', 'numeric', 'min:0'],
            'price' => ['required', 'numeric', 'min:0'],
            'sku' => [
                'required',
                'string',
                'max:255',
                Rule::unique('shop_products', 'sku')
                    ->where('tenant_id', $this->tenantId)
                    ->ignore($this->editingProductId),
            ],
            'shortDescription' => ['nullable', 'string', 'max:500'],
            'longDescription' => ['nullable', 'string'],
            'totalUnit' => ['nullable', 'integer', 'min:0'],
            'unitType' => ['nullable', 'string', 'max:255'],
            'storage' => ['nullable', 'string', 'max:255'],
            'sortNo' => ['nullable', 'integer'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'uploadedImages.*' => ['image', 'max:4096'],
            'coverSelection' => ['nullable', 'string'],
        ];
    }

    private function normalizedImages(): array
    {
        $images = collect($this->existingImages)
            ->map(fn (array $image, int $index): array => [
                'filename' => $image['filename'],
                'is_cover' => $this->coverSelection === "existing-{$index}",
            ])
            ->values();

        foreach ($this->uploadedImages as $index => $upload) {
            if (! $upload instanceof TemporaryUploadedFile) {
                continue;
            }

            $path = $upload->storePubliclyAs(
                "products/{$this->tenantId}",
                Str::uuid().'.'.$upload->getClientOriginalExtension()
            );

            $images->push([
                'filename' => Storage::disk(config('filesystems.default'))->url($path),
                'is_cover' => $this->coverSelection === "upload-{$index}",
            ]);
        }

        if ($images->isNotEmpty() && ! $images->contains('is_cover', true)) {
            $first = $images->first();
            $first['is_cover'] = true;
            $images = $images->replace([0 => $first]);
        }

        return $images->values()->all();
    }

    private function stockMovements()
    {
        if (! $this->editingProductId) {
            return collect();
        }

        return ShopProductStock::query()
            ->where('tenant_id', $this->tenantId)
            ->where('shop_product_id', $this->editingProductId)
            ->orderByDesc('id')
            ->limit(20)
            ->get();
    }

    private function findScopedProduct(int $productId): ShopProduct
    {
        $product = ShopProduct::query()
            ->where('tenant_id', $this->tenantId)
            ->whereKey($productId)
            ->first();

        abort_if(! $product, 404);

        return $product;
    }

    private function resetForm(): void
    {
        $this->reset([
            'editingProductId',
            'isEditing',
            'name',
            'normalPrice',
            'price',
            'sku',
            'shortDescription',
            'longDescription',
            'totalUnit',
            'unitType',
            'storage',
            'sortNo',
            'existingImages',
            'uploadedImages',
            'coverSelection',
        ]);

        $this->status = 'active';
        $this->resetStockForm();
        $this->resetValidation();
    }

    private function resetStockForm(): void
    {
        $this->stockType = 'UPDATE';
        $this->stockQuantity = 0;
        $this->stockRemark = '';
    }

    private function defaultCoverSelection(array $images): ?string
    {
        foreach ($images as $index => $image) {
            if (($image['is_cover'] ?? false) === true) {
                return "existing-{$index}";
            }
        }

        return $images === [] ? null : 'existing-0';
    }

    private function blankToNull(mixed $value): mixed
    {
        return blank($value) ? null : $value;
    }
}

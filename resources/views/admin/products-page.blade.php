<section class="space-y-8">
  <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
    <div>
      <h1 class="text-2xl font-semibold">Products</h1>
      <p class="mt-2 text-sm text-slate-500">Manage products and stock for the current shop.</p>
    </div>

    <button type="button" wire:click="create" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white">
      New product
    </button>
  </div>

  <div class="rounded-lg border border-slate-200 bg-white p-6">
    <div class="grid gap-4 lg:grid-cols-3">
      <div class="lg:col-span-2">
        <label for="search" class="text-sm font-medium text-slate-700">Search</label>
        <input id="search" type="search" wire:model.live.debounce.300ms="search" class="mt-1 w-full rounded-lg border-slate-300" placeholder="Name or SKU">
      </div>

      <div>
        <label for="statusFilter" class="text-sm font-medium text-slate-700">Status</label>
        <select id="statusFilter" wire:model.live="statusFilter" class="mt-1 w-full rounded-lg border-slate-300">
          <option value="">All statuses</option>
          <option value="active">Active</option>
          <option value="inactive">Inactive</option>
        </select>
      </div>
    </div>
  </div>

  @if ($isEditing)
    <form wire:submit="save" class="space-y-6 rounded-lg border border-slate-200 bg-white p-6">
      <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
        <h2 class="text-lg font-semibold">{{ $editingProductId ? 'Edit product' : 'Create product' }}</h2>
        <button type="button" wire:click="cancelEdit" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700">
          Cancel
        </button>
      </div>

      <div class="grid gap-4 lg:grid-cols-3">
        <div class="lg:col-span-2">
          <label for="name" class="text-sm font-medium text-slate-700">Name</label>
          <input id="name" type="text" wire:model="name" class="mt-1 w-full rounded-lg border-slate-300">
          @error('name') <div class="mt-1 text-sm text-red-700">{{ $message }}</div> @enderror
        </div>

        <div>
          <label for="sku" class="text-sm font-medium text-slate-700">SKU</label>
          <input id="sku" type="text" wire:model="sku" class="mt-1 w-full rounded-lg border-slate-300">
          @error('sku') <div class="mt-1 text-sm text-red-700">{{ $message }}</div> @enderror
        </div>

        <div>
          <label for="normalPrice" class="text-sm font-medium text-slate-700">Normal price</label>
          <input id="normalPrice" type="number" step="0.01" min="0" wire:model="normalPrice" class="mt-1 w-full rounded-lg border-slate-300">
          @error('normalPrice') <div class="mt-1 text-sm text-red-700">{{ $message }}</div> @enderror
        </div>

        <div>
          <label for="price" class="text-sm font-medium text-slate-700">Price</label>
          <input id="price" type="number" step="0.01" min="0" wire:model="price" class="mt-1 w-full rounded-lg border-slate-300">
          @error('price') <div class="mt-1 text-sm text-red-700">{{ $message }}</div> @enderror
        </div>

        <div>
          <label for="status" class="text-sm font-medium text-slate-700">Status</label>
          <select id="status" wire:model="status" class="mt-1 w-full rounded-lg border-slate-300">
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
          </select>
          @error('status') <div class="mt-1 text-sm text-red-700">{{ $message }}</div> @enderror
        </div>

        <div>
          <label for="totalUnit" class="text-sm font-medium text-slate-700">Total unit</label>
          <input id="totalUnit" type="number" min="0" wire:model="totalUnit" class="mt-1 w-full rounded-lg border-slate-300">
          @error('totalUnit') <div class="mt-1 text-sm text-red-700">{{ $message }}</div> @enderror
        </div>

        <div>
          <label for="unitType" class="text-sm font-medium text-slate-700">Unit type</label>
          <input id="unitType" type="text" wire:model="unitType" class="mt-1 w-full rounded-lg border-slate-300">
          @error('unitType') <div class="mt-1 text-sm text-red-700">{{ $message }}</div> @enderror
        </div>

        <div>
          <label for="sortNo" class="text-sm font-medium text-slate-700">Sort number</label>
          <input id="sortNo" type="number" wire:model="sortNo" class="mt-1 w-full rounded-lg border-slate-300">
          @error('sortNo') <div class="mt-1 text-sm text-red-700">{{ $message }}</div> @enderror
        </div>

        <div class="lg:col-span-3">
          <label for="storage" class="text-sm font-medium text-slate-700">Storage</label>
          <input id="storage" type="text" wire:model="storage" class="mt-1 w-full rounded-lg border-slate-300">
          @error('storage') <div class="mt-1 text-sm text-red-700">{{ $message }}</div> @enderror
        </div>

        <div class="lg:col-span-3">
          <label for="shortDescription" class="text-sm font-medium text-slate-700">Short description</label>
          <input id="shortDescription" type="text" wire:model="shortDescription" class="mt-1 w-full rounded-lg border-slate-300">
          @error('shortDescription') <div class="mt-1 text-sm text-red-700">{{ $message }}</div> @enderror
        </div>

        <div class="lg:col-span-3">
          <label for="longDescription" class="text-sm font-medium text-slate-700">Long description</label>
          <textarea id="longDescription" rows="5" wire:model="longDescription" class="mt-1 w-full rounded-lg border-slate-300"></textarea>
          @error('longDescription') <div class="mt-1 text-sm text-red-700">{{ $message }}</div> @enderror
        </div>
      </div>

      <div class="space-y-4">
        <div>
          <label for="uploadedImages" class="text-sm font-medium text-slate-700">Images</label>
          <input id="uploadedImages" type="file" multiple wire:model="uploadedImages" class="mt-1 block w-full text-sm text-slate-700">
          @error('uploadedImages.*') <div class="mt-1 text-sm text-red-700">{{ $message }}</div> @enderror
        </div>

        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
          @foreach ($existingImages as $index => $image)
            <label class="rounded-lg border border-slate-200 p-3">
              <img src="{{ $image['filename'] }}" alt="" class="aspect-square w-full rounded object-cover">
              <span class="mt-2 flex items-center gap-2 text-sm text-slate-700">
                <input type="radio" wire:model="coverSelection" value="existing-{{ $index }}" class="border-slate-300">
                Cover
              </span>
            </label>
          @endforeach

          @foreach ($uploadedImages as $index => $image)
            <label class="rounded-lg border border-slate-200 p-3">
              <div class="flex aspect-square w-full items-center justify-center rounded bg-slate-100 text-sm text-slate-500">
                New image {{ $index + 1 }}
              </div>
              <span class="mt-2 flex items-center gap-2 text-sm text-slate-700">
                <input type="radio" wire:model="coverSelection" value="upload-{{ $index }}" class="border-slate-300">
                Cover
              </span>
            </label>
          @endforeach
        </div>
      </div>

      <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white">
        Save product
      </button>
    </form>
  @endif

  @if ($editingProductId)
    <div class="grid gap-6 xl:grid-cols-2">
      <div class="rounded-lg border border-slate-200 bg-white p-6">
        <h2 class="text-lg font-semibold">Adjust stock</h2>

        <form wire:submit="adjustStock" class="mt-6 grid gap-4 lg:grid-cols-3">
          <div>
            <label for="stockType" class="text-sm font-medium text-slate-700">Type</label>
            <select id="stockType" wire:model="stockType" class="mt-1 w-full rounded-lg border-slate-300">
              <option value="UPDATE">Update (+/-)</option>
              <option value="SET">Set exact</option>
            </select>
            @error('stockType') <div class="mt-1 text-sm text-red-700">{{ $message }}</div> @enderror
          </div>

          <div>
            <label for="stockQuantity" class="text-sm font-medium text-slate-700">Quantity</label>
            <input id="stockQuantity" type="number" wire:model="stockQuantity" class="mt-1 w-full rounded-lg border-slate-300">
            @error('stockQuantity') <div class="mt-1 text-sm text-red-700">{{ $message }}</div> @enderror
          </div>

          <div class="lg:col-span-3">
            <label for="stockRemark" class="text-sm font-medium text-slate-700">Remark</label>
            <input id="stockRemark" type="text" wire:model="stockRemark" class="mt-1 w-full rounded-lg border-slate-300">
            @error('stockRemark') <div class="mt-1 text-sm text-red-700">{{ $message }}</div> @enderror
          </div>

          <div class="lg:col-span-3">
            <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white">
              Apply stock change
            </button>
          </div>
        </form>
      </div>

      <div class="overflow-hidden rounded-lg border border-slate-200 bg-white">
        <div class="border-b border-slate-200 p-6">
          <h2 class="text-lg font-semibold">Stock movement history</h2>
        </div>

        <table class="min-w-full divide-y divide-slate-200 text-sm">
          <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
            <tr>
              <th class="px-4 py-3">Date</th>
              <th class="px-4 py-3">Qty</th>
              <th class="px-4 py-3">Remark</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200">
            @forelse ($stockMovements as $movement)
              <tr>
                <td class="px-4 py-3 text-slate-600">{{ $movement->created_at?->format('Y-m-d H:i') }}</td>
                <td class="px-4 py-3 font-medium text-slate-900">{{ $movement->quantity }}</td>
                <td class="px-4 py-3 text-slate-600">{{ $movement->remark }}</td>
              </tr>
            @empty
              <tr>
                <td colspan="3" class="px-4 py-6 text-center text-slate-500">No stock movements yet.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  @endif

  <div class="overflow-hidden rounded-lg border border-slate-200 bg-white">
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
          <tr>
            <th class="px-4 py-3">Sort</th>
            <th class="px-4 py-3">Product</th>
            <th class="px-4 py-3">SKU</th>
            <th class="px-4 py-3">Price</th>
            <th class="px-4 py-3">Stock</th>
            <th class="px-4 py-3">Status</th>
            <th class="px-4 py-3">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-200">
          @forelse ($products as $product)
            <tr>
              <td class="px-4 py-3 text-slate-600">{{ $product->sort_no }}</td>
              <td class="px-4 py-3">
                <div class="flex items-center gap-3">
                  @if ($product->cover_image)
                    <img src="{{ $product->cover_image }}" alt="" class="size-12 rounded object-cover">
                  @endif
                  <span class="font-medium text-slate-900">{{ $product->name }}</span>
                </div>
              </td>
              <td class="px-4 py-3 text-slate-600">{{ $product->sku }}</td>
              <td class="px-4 py-3 text-slate-600">{{ number_format((float) $product->price, 2) }}</td>
              <td class="px-4 py-3 text-slate-600">{{ $product->available_quantity }}</td>
              <td class="px-4 py-3 text-slate-600">{{ ucfirst($product->status) }}</td>
              <td class="px-4 py-3">
                <button type="button" wire:click="edit({{ $product->id }})" class="rounded-lg border border-slate-300 px-3 py-2 font-medium text-slate-700">
                  Edit
                </button>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="px-4 py-6 text-center text-slate-500">No products found.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="border-t border-slate-200 p-4">
      {{ $products->links() }}
    </div>
  </div>
</section>

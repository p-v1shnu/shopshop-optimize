<section class="space-y-8">
  <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
    <div>
      <h1 class="text-2xl font-semibold">Coupons</h1>
      <p class="mt-2 text-sm text-slate-500">Manage public and private coupons for the current shop.</p>
    </div>

    <button type="button" wire:click="create" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white">
      New coupon
    </button>
  </div>

  <div class="rounded-lg border border-slate-200 bg-white p-6">
    <div class="grid gap-4 lg:grid-cols-4">
      <div class="lg:col-span-2">
        <label for="search" class="text-sm font-medium text-slate-700">Search code</label>
        <input id="search" type="search" wire:model.live.debounce.300ms="search" class="mt-1 w-full rounded-lg border-slate-300">
      </div>

      <div>
        <label for="statusFilter" class="text-sm font-medium text-slate-700">Status</label>
        <select id="statusFilter" wire:model.live="statusFilter" class="mt-1 w-full rounded-lg border-slate-300">
          <option value="">All statuses</option>
          <option value="active">Active</option>
          <option value="inactive">Inactive</option>
          <option value="expired">Expired</option>
          <option value="sold_out">Sold out</option>
        </select>
      </div>

      <div>
        <label for="typeFilter" class="text-sm font-medium text-slate-700">Type</label>
        <select id="typeFilter" wire:model.live="typeFilter" class="mt-1 w-full rounded-lg border-slate-300">
          <option value="">All types</option>
          <option value="fixed">Fixed</option>
          <option value="percentage">Percentage</option>
        </select>
      </div>
    </div>
  </div>

  @if ($isEditing)
    <form wire:submit="save" class="space-y-6 rounded-lg border border-slate-200 bg-white p-6">
      <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
        <h2 class="text-lg font-semibold">{{ $editingCouponId ? 'Edit coupon' : 'Create coupon' }}</h2>
        <button type="button" wire:click="cancelEdit" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700">
          Cancel
        </button>
      </div>

      <div class="grid gap-4 lg:grid-cols-3">
        <div>
          <label for="code" class="text-sm font-medium text-slate-700">Code</label>
          <input id="code" type="text" wire:model="code" class="mt-1 w-full rounded-lg border-slate-300">
          @error('code') <div class="mt-1 text-sm text-red-700">{{ $message }}</div> @enderror
        </div>

        <div>
          <label for="type" class="text-sm font-medium text-slate-700">Type</label>
          <select id="type" wire:model.live="type" class="mt-1 w-full rounded-lg border-slate-300">
            <option value="fixed">Fixed</option>
            <option value="percentage">Percentage</option>
          </select>
          @error('type') <div class="mt-1 text-sm text-red-700">{{ $message }}</div> @enderror
        </div>

        <div>
          <label for="amount" class="text-sm font-medium text-slate-700">Amount</label>
          <input id="amount" type="number" step="0.01" wire:model="amount" class="mt-1 w-full rounded-lg border-slate-300">
          @error('amount') <div class="mt-1 text-sm text-red-700">{{ $message }}</div> @enderror
        </div>

        <div>
          <label for="startedAt" class="text-sm font-medium text-slate-700">Starts at</label>
          <input id="startedAt" type="datetime-local" wire:model="startedAt" class="mt-1 w-full rounded-lg border-slate-300">
          @error('startedAt') <div class="mt-1 text-sm text-red-700">{{ $message }}</div> @enderror
        </div>

        <div>
          <label for="endedAt" class="text-sm font-medium text-slate-700">Ends at</label>
          <input id="endedAt" type="datetime-local" wire:model="endedAt" class="mt-1 w-full rounded-lg border-slate-300">
          @error('endedAt') <div class="mt-1 text-sm text-red-700">{{ $message }}</div> @enderror
        </div>

        <div>
          <label for="status" class="text-sm font-medium text-slate-700">Status</label>
          <select id="status" wire:model="status" class="mt-1 w-full rounded-lg border-slate-300">
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
            <option value="expired">Expired</option>
            <option value="sold_out">Sold out</option>
          </select>
          @error('status') <div class="mt-1 text-sm text-red-700">{{ $message }}</div> @enderror
        </div>

        <div>
          <label for="totalQuantity" class="text-sm font-medium text-slate-700">Total quantity</label>
          <input id="totalQuantity" type="number" min="0" wire:model="totalQuantity" class="mt-1 w-full rounded-lg border-slate-300">
          @error('totalQuantity') <div class="mt-1 text-sm text-red-700">{{ $message }}</div> @enderror
        </div>

        <div>
          <label for="availableQuantity" class="text-sm font-medium text-slate-700">Available quantity</label>
          <input id="availableQuantity" type="number" min="0" wire:model="availableQuantity" class="mt-1 w-full rounded-lg border-slate-300">
          @error('availableQuantity') <div class="mt-1 text-sm text-red-700">{{ $message }}</div> @enderror
        </div>

        <div>
          <label for="userDailyLimit" class="text-sm font-medium text-slate-700">User daily limit</label>
          <input id="userDailyLimit" type="number" min="0" wire:model="userDailyLimit" class="mt-1 w-full rounded-lg border-slate-300">
          @error('userDailyLimit') <div class="mt-1 text-sm text-red-700">{{ $message }}</div> @enderror
        </div>

        <div>
          <label for="minimumOrderAmount" class="text-sm font-medium text-slate-700">Minimum order amount</label>
          <input id="minimumOrderAmount" type="number" step="0.01" min="0" wire:model="minimumOrderAmount" class="mt-1 w-full rounded-lg border-slate-300">
          @error('minimumOrderAmount') <div class="mt-1 text-sm text-red-700">{{ $message }}</div> @enderror
        </div>

        <div class="lg:col-span-2">
          <label for="userId" class="text-sm font-medium text-slate-700">Private customer</label>
          <select id="userId" wire:model="userId" class="mt-1 w-full rounded-lg border-slate-300">
            <option value="">Public coupon</option>
            @foreach ($customers as $customer)
              <option value="{{ $customer->id }}">{{ $customer->name ?? 'Unnamed customer' }} · {{ $customer->phone }}</option>
            @endforeach
          </select>
          @error('userId') <div class="mt-1 text-sm text-red-700">{{ $message }}</div> @enderror
        </div>

        <div class="lg:col-span-3">
          <label for="remark" class="text-sm font-medium text-slate-700">Remark</label>
          <textarea id="remark" rows="3" wire:model="remark" class="mt-1 w-full rounded-lg border-slate-300"></textarea>
          @error('remark') <div class="mt-1 text-sm text-red-700">{{ $message }}</div> @enderror
        </div>
      </div>

      <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white">
        Save coupon
      </button>
    </form>
  @endif

  @if ($editingCouponId)
    <div class="overflow-hidden rounded-lg border border-slate-200 bg-white">
      <div class="border-b border-slate-200 p-6">
        <h2 class="text-lg font-semibold">Usage history</h2>
      </div>

      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
          <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
            <tr>
              <th class="px-4 py-3">Order</th>
              <th class="px-4 py-3">Customer</th>
              <th class="px-4 py-3">Discount</th>
              <th class="px-4 py-3">Used at</th>
              <th class="px-4 py-3">Remark</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200">
            @forelse ($usageHistory as $usage)
              <tr>
                <td class="px-4 py-3 font-medium text-slate-900">{{ $usage->shopOrder?->order_code ?? $usage->shop_order_id }}</td>
                <td class="px-4 py-3 text-slate-600">{{ $usage->user?->name ?? '-' }} @if($usage->user?->phone) · {{ $usage->user->phone }} @endif</td>
                <td class="px-4 py-3 text-slate-600">{{ number_format((float) $usage->discount_amount, 2) }}</td>
                <td class="px-4 py-3 text-slate-600">{{ $usage->created_at?->format('Y-m-d H:i') }}</td>
                <td class="px-4 py-3 text-slate-600">{{ $usage->remark }}</td>
              </tr>
            @empty
              <tr>
                <td colspan="5" class="px-4 py-6 text-center text-slate-500">No coupon usage yet.</td>
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
            <th class="px-4 py-3">Code</th>
            <th class="px-4 py-3">Type</th>
            <th class="px-4 py-3">Amount</th>
            <th class="px-4 py-3">Quantity</th>
            <th class="px-4 py-3">Customer</th>
            <th class="px-4 py-3">Status</th>
            <th class="px-4 py-3">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-200">
          @forelse ($coupons as $coupon)
            <tr>
              <td class="px-4 py-3 font-medium text-slate-900">{{ $coupon->code }}</td>
              <td class="px-4 py-3 text-slate-600">{{ ucfirst($coupon->type) }}</td>
              <td class="px-4 py-3 text-slate-600">{{ number_format((float) $coupon->amount, 2) }}</td>
              <td class="px-4 py-3 text-slate-600">{{ number_format($coupon->available_quantity) }} / {{ number_format($coupon->total_quantity) }}</td>
              <td class="px-4 py-3 text-slate-600">{{ $coupon->user?->name ?? 'Public' }}</td>
              <td class="px-4 py-3 text-slate-600">{{ ucfirst(str_replace('_', ' ', $coupon->status)) }}</td>
              <td class="px-4 py-3">
                <button type="button" wire:click="edit({{ $coupon->id }})" class="rounded-lg border border-slate-300 px-3 py-2 font-medium text-slate-700">
                  Edit
                </button>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="px-4 py-6 text-center text-slate-500">No coupons found.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="border-t border-slate-200 p-4">
      {{ $coupons->links() }}
    </div>
  </div>
</section>

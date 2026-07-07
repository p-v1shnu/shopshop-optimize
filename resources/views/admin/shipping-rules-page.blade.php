<section class="space-y-8">
  <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
    <div>
      <h1 class="text-2xl font-semibold">Shipping rules</h1>
      <p class="mt-2 text-sm text-slate-500">Manage shipping fee rules for the current shop.</p>
    </div>

    <button type="button" wire:click="create" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white">
      New rule
    </button>
  </div>

  <div class="rounded-lg border border-slate-200 bg-white p-6">
    <div class="max-w-xs">
      <label for="statusFilter" class="text-sm font-medium text-slate-700">Status</label>
      <select id="statusFilter" wire:model.live="statusFilter" class="mt-1 w-full rounded-lg border-slate-300">
        <option value="">All statuses</option>
        <option value="active">Active</option>
        <option value="inactive">Inactive</option>
      </select>
    </div>
  </div>

  @if ($isEditing)
    <form wire:submit="save" class="space-y-6 rounded-lg border border-slate-200 bg-white p-6">
      <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
        <h2 class="text-lg font-semibold">{{ $editingRuleId ? 'Edit shipping rule' : 'Create shipping rule' }}</h2>
        <button type="button" wire:click="cancelEdit" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700">
          Cancel
        </button>
      </div>

      <div class="grid gap-4 lg:grid-cols-3">
        <div>
          <label for="status" class="text-sm font-medium text-slate-700">Status</label>
          <select id="status" wire:model="status" class="mt-1 w-full rounded-lg border-slate-300">
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
          </select>
          @error('status') <div class="mt-1 text-sm text-red-700">{{ $message }}</div> @enderror
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
          <label for="minimumAmount" class="text-sm font-medium text-slate-700">Minimum amount</label>
          <input id="minimumAmount" type="number" step="0.01" min="0" wire:model="minimumAmount" class="mt-1 w-full rounded-lg border-slate-300">
          @error('minimumAmount') <div class="mt-1 text-sm text-red-700">{{ $message }}</div> @enderror
        </div>

        <div>
          <label for="shippingFeeType" class="text-sm font-medium text-slate-700">Shipping fee type</label>
          <select id="shippingFeeType" wire:model="shippingFeeType" class="mt-1 w-full rounded-lg border-slate-300">
            <option value="cod">COD</option>
            <option value="free">Free</option>
            <option value="prepaid">Prepaid</option>
          </select>
          @error('shippingFeeType') <div class="mt-1 text-sm text-red-700">{{ $message }}</div> @enderror
        </div>

        <div>
          <label for="shippingDaysText" class="text-sm font-medium text-slate-700">Shipping days text</label>
          <input id="shippingDaysText" type="text" wire:model="shippingDaysText" class="mt-1 w-full rounded-lg border-slate-300">
          @error('shippingDaysText') <div class="mt-1 text-sm text-red-700">{{ $message }}</div> @enderror
        </div>

        <div class="lg:col-span-3">
          <label for="remark" class="text-sm font-medium text-slate-700">Remark</label>
          <textarea id="remark" rows="3" wire:model="remark" class="mt-1 w-full rounded-lg border-slate-300"></textarea>
          @error('remark') <div class="mt-1 text-sm text-red-700">{{ $message }}</div> @enderror
        </div>
      </div>

      <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white">
        Save rule
      </button>
    </form>
  @endif

  <div class="overflow-hidden rounded-lg border border-slate-200 bg-white">
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
          <tr>
            <th class="px-4 py-3">Status</th>
            <th class="px-4 py-3">Date range</th>
            <th class="px-4 py-3">Minimum amount</th>
            <th class="px-4 py-3">Fee type</th>
            <th class="px-4 py-3">Days</th>
            <th class="px-4 py-3">Remark</th>
            <th class="px-4 py-3">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-200">
          @forelse ($rules as $rule)
            <tr>
              <td class="px-4 py-3 text-slate-600">{{ ucfirst($rule->status) }}</td>
              <td class="px-4 py-3 text-slate-600">
                {{ $rule->started_at?->format('Y-m-d H:i') }} to {{ $rule->ended_at?->format('Y-m-d H:i') }}
              </td>
              <td class="px-4 py-3 text-slate-600">{{ number_format((float) $rule->minimum_amount, 2) }}</td>
              <td class="px-4 py-3 text-slate-600">{{ strtoupper($rule->shipping_fee_type) }}</td>
              <td class="px-4 py-3 font-medium text-slate-900">{{ $rule->shipping_days_text }}</td>
              <td class="px-4 py-3 text-slate-600">{{ $rule->remark ?? '-' }}</td>
              <td class="px-4 py-3">
                <div class="flex gap-2">
                  <button type="button" wire:click="edit({{ $rule->id }})" class="rounded-lg border border-slate-300 px-3 py-2 font-medium text-slate-700">
                    Edit
                  </button>
                  <button type="button" wire:click="delete({{ $rule->id }})" wire:confirm="Delete this shipping rule?" class="rounded-lg border border-red-200 px-3 py-2 font-medium text-red-700">
                    Delete
                  </button>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="px-4 py-6 text-center text-slate-500">No shipping rules found.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="border-t border-slate-200 p-4">
      {{ $rules->links() }}
    </div>
  </div>
</section>

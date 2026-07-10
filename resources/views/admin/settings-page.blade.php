<section class="space-y-8">
  <div>
    <h1 class="text-2xl font-semibold">Settings</h1>
    <p class="mt-2 text-sm text-slate-500">Manage storefront settings for {{ $tenant->name }}.</p>
  </div>

  <form wire:submit="save" class="space-y-8">
    <div class="rounded-lg border border-slate-200 bg-white p-6">
      <h2 class="text-lg font-semibold">Shop availability</h2>

      <div class="mt-6 grid gap-4 lg:grid-cols-2">
        <label class="flex items-center gap-2 rounded-lg border border-slate-200 p-4">
          <input type="checkbox" wire:model="enableShop" class="rounded border-slate-300">
          <span class="text-sm font-medium text-slate-700">Enable shop</span>
        </label>

        <label class="flex items-center gap-2 rounded-lg border border-slate-200 p-4">
          <input type="checkbox" wire:model="enableCoupon" class="rounded border-slate-300">
          <span class="text-sm font-medium text-slate-700">Enable coupons</span>
        </label>

        <div class="lg:col-span-2">
          <label for="shopClosedAt" class="text-sm font-medium text-slate-700">Shop close date/time</label>
          <input id="shopClosedAt" type="datetime-local" wire:model="shopClosedAt" class="mt-1 w-full rounded-lg border-slate-300">
          @error('shopClosedAt') <div class="mt-1 text-sm text-red-700">{{ $message }}</div> @enderror
        </div>
      </div>
    </div>

    <div class="rounded-lg border border-slate-200 bg-white p-6">
      <h2 class="text-lg font-semibold">Campaign</h2>

      <div class="mt-6 grid gap-4 lg:grid-cols-3">
        <div>
          <label for="campaignCode" class="text-sm font-medium text-slate-700">Campaign code</label>
          <input id="campaignCode" type="text" wire:model="campaignCode" class="mt-1 w-full rounded-lg border-slate-300">
          @error('campaignCode') <div class="mt-1 text-sm text-red-700">{{ $message }}</div> @enderror
        </div>

        <div>
          <label for="campaignStartsAt" class="text-sm font-medium text-slate-700">Starts at</label>
          <input id="campaignStartsAt" type="datetime-local" wire:model="campaignStartsAt" class="mt-1 w-full rounded-lg border-slate-300">
          @error('campaignStartsAt') <div class="mt-1 text-sm text-red-700">{{ $message }}</div> @enderror
        </div>

        <div>
          <label for="campaignEndsAt" class="text-sm font-medium text-slate-700">Ends at</label>
          <input id="campaignEndsAt" type="datetime-local" wire:model="campaignEndsAt" class="mt-1 w-full rounded-lg border-slate-300">
          @error('campaignEndsAt') <div class="mt-1 text-sm text-red-700">{{ $message }}</div> @enderror
        </div>
      </div>
    </div>

    <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white">
      Save settings
    </button>
  </form>
</section>

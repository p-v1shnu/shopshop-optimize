<section class="space-y-8">
  <div>
    <h1 class="text-2xl font-semibold">Banners</h1>
    <p class="mt-2 text-sm text-slate-500">Manage homepage and popup banners for {{ $tenant->name }}.</p>
  </div>

  <div class="grid gap-8 xl:grid-cols-2">
    <div class="space-y-4 rounded-lg border border-slate-200 bg-white p-6">
      <div>
        <h2 class="text-lg font-semibold">Homepage banners</h2>
        <p class="mt-1 text-sm text-slate-500">Shown in the storefront header carousel.</p>
      </div>

      <form wire:submit="addHomepageBanners" class="space-y-3">
        <label for="homepageUploads" class="text-sm font-medium text-slate-700">Upload images</label>
        <input id="homepageUploads" type="file" multiple wire:model="homepageUploads" class="block w-full text-sm text-slate-700">
        @error('homepageUploads.*') <div class="text-sm text-red-700">{{ $message }}</div> @enderror
        <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white">
          Add homepage banners
        </button>
      </form>

      <div class="space-y-3">
        @forelse ($homepageBanners as $index => $banner)
          <div class="flex gap-3 rounded-lg border border-slate-200 p-3">
            <img src="{{ $banner }}" alt="" class="h-24 w-32 flex-none rounded object-cover">
            <div class="min-w-0 flex-1">
              <div class="truncate text-sm font-medium text-slate-900">{{ $banner }}</div>
              <div class="mt-3 flex flex-wrap gap-2">
                <button type="button" wire:click="moveHomepageBanner({{ $index }}, 'up')" @disabled($index === 0) class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 disabled:opacity-40">
                  Up
                </button>
                <button type="button" wire:click="moveHomepageBanner({{ $index }}, 'down')" @disabled($index === count($homepageBanners) - 1) class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 disabled:opacity-40">
                  Down
                </button>
                <button type="button" wire:click="removeHomepageBanner({{ $index }})" class="rounded-lg border border-red-200 px-3 py-2 text-sm font-medium text-red-700">
                  Remove
                </button>
              </div>
            </div>
          </div>
        @empty
          <div class="rounded-lg border border-dashed border-slate-300 p-6 text-center text-sm text-slate-500">
            No homepage banners yet.
          </div>
        @endforelse
      </div>
    </div>

    <div class="space-y-4 rounded-lg border border-slate-200 bg-white p-6">
      <div>
        <h2 class="text-lg font-semibold">Popup banners</h2>
        <p class="mt-1 text-sm text-slate-500">Shown in the storefront promotion popup.</p>
      </div>

      <form wire:submit="addPopupBanners" class="space-y-3">
        <label for="popupUploads" class="text-sm font-medium text-slate-700">Upload images</label>
        <input id="popupUploads" type="file" multiple wire:model="popupUploads" class="block w-full text-sm text-slate-700">
        @error('popupUploads.*') <div class="text-sm text-red-700">{{ $message }}</div> @enderror
        <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white">
          Add popup banners
        </button>
      </form>

      <div class="space-y-3">
        @forelse ($popupBanners as $index => $banner)
          <div class="flex gap-3 rounded-lg border border-slate-200 p-3">
            <img src="{{ $banner }}" alt="" class="h-24 w-32 flex-none rounded object-cover">
            <div class="min-w-0 flex-1">
              <div class="truncate text-sm font-medium text-slate-900">{{ $banner }}</div>
              <div class="mt-3 flex flex-wrap gap-2">
                <button type="button" wire:click="movePopupBanner({{ $index }}, 'up')" @disabled($index === 0) class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 disabled:opacity-40">
                  Up
                </button>
                <button type="button" wire:click="movePopupBanner({{ $index }}, 'down')" @disabled($index === count($popupBanners) - 1) class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 disabled:opacity-40">
                  Down
                </button>
                <button type="button" wire:click="removePopupBanner({{ $index }})" class="rounded-lg border border-red-200 px-3 py-2 text-sm font-medium text-red-700">
                  Remove
                </button>
              </div>
            </div>
          </div>
        @empty
          <div class="rounded-lg border border-dashed border-slate-300 p-6 text-center text-sm text-slate-500">
            No popup banners yet.
          </div>
        @endforelse
      </div>
    </div>
  </div>
</section>

<section class="space-y-8">
  <div>
    <h1 class="text-2xl font-semibold">Central settings</h1>
    <p class="mt-2 text-sm text-slate-500">Manage global platform settings.</p>
  </div>

  <form wire:submit="save" class="space-y-8">
    <div class="rounded-lg border border-slate-200 bg-white p-6">
      <h2 class="text-lg font-semibold">Platform content</h2>

      <div class="mt-6 grid gap-4">
        <div>
          <label for="title" class="text-sm font-medium text-slate-700">Title</label>
          <input id="title" type="text" wire:model="title" class="mt-1 w-full rounded-lg border-slate-300">
          @error('title') <div class="mt-1 text-sm text-red-700">{{ $message }}</div> @enderror
        </div>

        <div>
          <label for="facebookCoverUrl" class="text-sm font-medium text-slate-700">Facebook cover URL</label>
          <input id="facebookCoverUrl" type="url" wire:model="facebookCoverUrl" class="mt-1 w-full rounded-lg border-slate-300">
          @error('facebookCoverUrl') <div class="mt-1 text-sm text-red-700">{{ $message }}</div> @enderror
        </div>

        <div>
          <label for="landingPageUrl" class="text-sm font-medium text-slate-700">Landing page URL</label>
          <input id="landingPageUrl" type="url" wire:model="landingPageUrl" class="mt-1 w-full rounded-lg border-slate-300">
          @error('landingPageUrl') <div class="mt-1 text-sm text-red-700">{{ $message }}</div> @enderror
        </div>
      </div>
    </div>

    <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white">
      Save central settings
    </button>
  </form>
</section>

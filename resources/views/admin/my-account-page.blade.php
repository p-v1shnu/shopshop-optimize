<section class="space-y-8">
  <div>
    <h1 class="text-2xl font-semibold">My account</h1>
    <p class="mt-2 text-sm text-slate-500">Change the password for your admin account.</p>
  </div>

  <form wire:submit="changePassword" class="max-w-2xl space-y-6 rounded-lg border border-slate-200 bg-white p-6">
    @if ($successMessage)
      <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
        {{ $successMessage }}
      </div>
    @endif

    <div>
      <label for="currentPassword" class="text-sm font-medium text-slate-700">Current password</label>
      <input id="currentPassword" type="password" wire:model="currentPassword" class="mt-1 w-full rounded-lg border-slate-300">
      @error('currentPassword') <div class="mt-1 text-sm text-red-700">{{ $message }}</div> @enderror
    </div>

    <div>
      <label for="newPassword" class="text-sm font-medium text-slate-700">New password</label>
      <input id="newPassword" type="password" wire:model="newPassword" class="mt-1 w-full rounded-lg border-slate-300">
      @error('newPassword') <div class="mt-1 text-sm text-red-700">{{ $message }}</div> @enderror
    </div>

    <div>
      <label for="newPasswordConfirmation" class="text-sm font-medium text-slate-700">Confirm new password</label>
      <input id="newPasswordConfirmation" type="password" wire:model="newPasswordConfirmation" class="mt-1 w-full rounded-lg border-slate-300">
      @error('newPasswordConfirmation') <div class="mt-1 text-sm text-red-700">{{ $message }}</div> @enderror
    </div>

    <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white">
      Change password
    </button>
  </form>
</section>

<main class="flex min-h-screen items-center justify-center p-6">
  <section class="w-full max-w-md rounded-lg border border-slate-200 bg-white p-8 shadow-sm">
    <div>
      <h1 class="text-2xl font-semibold">ShopShop Admin</h1>
      <p class="mt-2 text-sm text-slate-500">Sign in with your admin email and password.</p>
    </div>

    <form wire:submit="login" class="mt-8 space-y-4">
      <div>
        <label for="email" class="text-sm font-medium text-slate-700">Email</label>
        <input id="email" type="email" wire:model="email" class="mt-1 w-full rounded-lg border-slate-300" autofocus>
        @error('email') <div class="mt-1 text-sm text-red-700">{{ $message }}</div> @enderror
      </div>

      <div>
        <label for="password" class="text-sm font-medium text-slate-700">Password</label>
        <input id="password" type="password" wire:model="password" class="mt-1 w-full rounded-lg border-slate-300">
        @error('password') <div class="mt-1 text-sm text-red-700">{{ $message }}</div> @enderror
      </div>

      <button type="submit" class="w-full rounded-lg bg-slate-900 px-4 py-3 text-sm font-semibold text-white">
        Login
      </button>
    </form>
  </section>
</main>

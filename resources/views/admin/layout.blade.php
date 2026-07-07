<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>{{ $title ?? 'ShopShop Admin' }}</title>
  @livewireStyles
  @vite(['resources/css/app.scss'])
</head>
<body class="min-h-full bg-slate-100 text-slate-950">
  @if ($guestLayout ?? false)
    {{ $slot }}
  @else
    <div class="min-h-screen lg:flex">
      <aside class="border-b border-slate-200 bg-white p-6 lg:w-72 lg:border-b-0 lg:border-r">
        <div>
          <div class="text-xl font-semibold">ShopShop Admin</div>
          <div class="mt-1 text-sm text-slate-500">Backoffice</div>
        </div>

        <nav class="mt-8 space-y-2">
          <a href="{{ route('admin.dashboard') }}" class="block rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white">
            Dashboard
          </a>
          <a href="{{ route('admin.products') }}" class="block rounded-lg px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">
            Products
          </a>
          <a href="{{ route('admin.orders') }}" class="block rounded-lg px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">
            Orders
          </a>
          <a href="{{ route('admin.coupons') }}" class="block rounded-lg px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">
            Coupons
          </a>
          <a href="{{ route('admin.shipping-rules') }}" class="block rounded-lg px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">
            Shipping rules
          </a>
          <a href="{{ route('admin.customers') }}" class="block rounded-lg px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">
            Customers
          </a>
          <a href="{{ route('admin.settings') }}" class="block rounded-lg px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">
            Settings
          </a>
          <a href="{{ route('admin.banners') }}" class="block rounded-lg px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">
            Banners
          </a>
          @if (auth('admin')->user()?->isSuper())
            <a href="{{ route('admin.brands') }}" class="block rounded-lg px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">
              Brands
            </a>
            <a href="{{ route('admin.admin-accounts') }}" class="block rounded-lg px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">
              Admin accounts
            </a>
          @endif
        </nav>
      </aside>

      <div class="min-w-0 flex-1">
        <header class="flex flex-col gap-4 border-b border-slate-200 bg-white p-6 md:flex-row md:items-center md:justify-between">
          <div>
            <div class="text-sm text-slate-500">Signed in as {{ auth('admin')->user()->email }}</div>
            <div class="text-lg font-semibold">{{ $currentAdminTenant?->name ?? 'No shop selected' }}</div>
          </div>

          <div class="flex flex-col gap-3 md:flex-row md:items-center">
            @if (auth('admin')->user()->isSuper())
              <form method="POST" action="{{ route('admin.current-shop.update') }}" class="flex items-center gap-2">
                @csrf
                <label for="tenant_id" class="text-sm font-medium text-slate-600">Current shop</label>
                <select id="tenant_id" name="tenant_id" class="rounded-lg border-slate-300 text-sm" onchange="this.form.submit()">
                  @foreach ($adminTenants as $tenant)
                    <option value="{{ $tenant->id }}" @selected($currentAdminTenant?->id === $tenant->id)>{{ $tenant->name }}</option>
                  @endforeach
                </select>
              </form>
            @endif

            <a href="{{ route('admin.my-account') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700">
              My account
            </a>

            <form method="POST" action="{{ route('admin.logout') }}">
              @csrf
              <button type="submit" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700">Logout</button>
            </form>
          </div>
        </header>

        <main class="p-6">
          {{ $slot }}
        </main>
      </div>
    </div>
  @endif

  @livewireScripts
</body>
</html>

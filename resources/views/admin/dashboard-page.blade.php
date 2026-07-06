<section class="space-y-8">
  <div>
    <h1 class="text-2xl font-semibold">Dashboard</h1>
    <p class="mt-2 text-sm text-slate-500">Shop performance at a glance.</p>
  </div>

  @if ($metrics === null)
    <div class="rounded-lg border border-slate-200 bg-white p-6">
      <h2 class="text-lg font-semibold">No shop selected</h2>
      <p class="mt-2 text-sm text-slate-500">Choose a shop from the switcher to view dashboard metrics.</p>
    </div>
  @else
    <div class="grid gap-6 lg:grid-cols-2 xl:grid-cols-4">
      <div class="rounded-lg border border-slate-200 bg-white p-6">
        <div class="text-sm font-medium text-slate-500">Today sales</div>
        <div class="mt-3 text-2xl font-semibold text-slate-950">
          {{ number_format($metrics['today_sales'], 2) }} LAK
        </div>
        <div class="mt-2 text-sm text-slate-500">{{ number_format($metrics['today_orders']) }} paid orders</div>
      </div>

      <div class="rounded-lg border border-slate-200 bg-white p-6">
        <div class="text-sm font-medium text-slate-500">Month sales</div>
        <div class="mt-3 text-2xl font-semibold text-slate-950">
          {{ number_format($metrics['month_sales'], 2) }} LAK
        </div>
        <div class="mt-2 text-sm text-slate-500">{{ number_format($metrics['month_orders']) }} paid orders</div>
      </div>

      <div class="rounded-lg border border-slate-200 bg-white p-6">
        <div class="text-sm font-medium text-slate-500">Pending to ship</div>
        <div class="mt-3 text-2xl font-semibold text-slate-950">
          {{ number_format($metrics['pending_to_ship']) }} orders
        </div>
        <div class="mt-2 text-sm text-slate-500">Paid orders with pending shipping</div>
      </div>

      <div class="rounded-lg border border-slate-200 bg-white p-6">
        <div class="text-sm font-medium text-slate-500">Low-stock products</div>
        <div class="mt-3 text-2xl font-semibold text-slate-950">
          {{ number_format($metrics['low_stock_products']) }} products
        </div>
        <div class="mt-2 text-sm text-slate-500">Threshold: {{ number_format($lowStockThreshold) }}</div>
      </div>
    </div>
  @endif
</section>

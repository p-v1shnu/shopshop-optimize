<section class="space-y-8">
  <div>
    <h1 class="text-2xl font-semibold">Search analytics</h1>
    <p class="mt-2 text-sm text-slate-500">Review customer search demand for the current shop.</p>
  </div>

  <div class="rounded-lg border border-slate-200 bg-white p-6">
    <div class="grid gap-4 md:grid-cols-2">
      <div>
        <label for="dateFrom" class="text-sm font-medium text-slate-700">From</label>
        <input id="dateFrom" type="date" wire:model.live="dateFrom" class="mt-1 w-full rounded-lg border-slate-300">
      </div>

      <div>
        <label for="dateTo" class="text-sm font-medium text-slate-700">To</label>
        <input id="dateTo" type="date" wire:model.live="dateTo" class="mt-1 w-full rounded-lg border-slate-300">
      </div>
    </div>
  </div>

  <div class="grid gap-8 xl:grid-cols-2">
    <div class="overflow-hidden rounded-lg border border-slate-200 bg-white">
      <div class="border-b border-slate-200 p-6">
        <h2 class="text-lg font-semibold">Top search terms</h2>
      </div>

      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
          <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
            <tr>
              <th class="px-4 py-3">Search term</th>
              <th class="px-4 py-3">Searches</th>
              <th class="px-4 py-3">Last searched</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200">
            @forelse ($searchTerms as $term)
              <tr>
                <td class="px-4 py-3 font-medium text-slate-900">{{ $term->search_term }}</td>
                <td class="px-4 py-3 text-slate-600">{{ number_format($term->search_count) }}</td>
                <td class="px-4 py-3 text-slate-600">{{ $term->last_searched_at ? \Illuminate\Support\Carbon::parse($term->last_searched_at)->format('Y-m-d H:i') : '-' }}</td>
              </tr>
            @empty
              <tr>
                <td colspan="3" class="px-4 py-6 text-center text-slate-500">No search terms found.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div class="border-t border-slate-200 p-4">
        {{ $searchTerms->links() }}
      </div>
    </div>

    <div class="overflow-hidden rounded-lg border border-slate-200 bg-white">
      <div class="border-b border-slate-200 p-6">
        <h2 class="text-lg font-semibold">Most-searched products</h2>
      </div>

      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
          <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
            <tr>
              <th class="px-4 py-3">Product</th>
              <th class="px-4 py-3">SKU</th>
              <th class="px-4 py-3">Searches</th>
              <th class="px-4 py-3">Status</th>
              <th class="px-4 py-3">Stock</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200">
            @forelse ($topProducts as $product)
              <tr>
                <td class="px-4 py-3 font-medium text-slate-900">{{ $product->name }}</td>
                <td class="px-4 py-3 text-slate-600">{{ $product->sku }}</td>
                <td class="px-4 py-3 text-slate-600">{{ number_format($product->total_search) }}</td>
                <td class="px-4 py-3 text-slate-600">{{ ucfirst($product->status) }}</td>
                <td class="px-4 py-3 text-slate-600">{{ number_format($product->available_quantity) }}</td>
              </tr>
            @empty
              <tr>
                <td colspan="5" class="px-4 py-6 text-center text-slate-500">No products found.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</section>

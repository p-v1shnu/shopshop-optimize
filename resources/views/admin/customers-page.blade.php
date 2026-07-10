<section class="space-y-8">
  <div>
    <h1 class="text-2xl font-semibold">Customers</h1>
    <p class="mt-2 text-sm text-slate-500">Review customer profiles and manage account bans for the current shop.</p>
  </div>

  <div class="rounded-lg border border-slate-200 bg-white p-6">
    <div class="max-w-xl">
      <label for="search" class="text-sm font-medium text-slate-700">Search customers</label>
      <input id="search" type="search" wire:model.live.debounce.300ms="search" class="mt-1 w-full rounded-lg border-slate-300" placeholder="Name or phone">
    </div>
  </div>

  <div class="overflow-hidden rounded-lg border border-slate-200 bg-white">
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
          <tr>
            <th class="px-4 py-3">Customer</th>
            <th class="px-4 py-3">Phone</th>
            <th class="px-4 py-3">Status</th>
            <th class="px-4 py-3">Banned at</th>
            <th class="px-4 py-3">Profile</th>
            <th class="px-4 py-3">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-200">
          @forelse ($customers as $customer)
            <tr>
              <td class="px-4 py-3 font-medium text-slate-900">{{ $customer->name ?? 'Unnamed customer' }}</td>
              <td class="px-4 py-3 text-slate-600">{{ $customer->phone ?? '-' }}</td>
              <td class="px-4 py-3 text-slate-600">{{ ucfirst($customer->status) }}</td>
              <td class="px-4 py-3 text-slate-600">{{ $customer->banned_at?->format('Y-m-d H:i') ?? '-' }}</td>
              <td class="px-4 py-3 text-slate-600">{{ $customer->had_complete_profile ? 'Complete' : 'Incomplete' }}</td>
              <td class="px-4 py-3">
                <button type="button" wire:click="selectCustomer({{ $customer->id }})" class="rounded-lg border border-slate-300 px-3 py-2 font-medium text-slate-700">
                  View
                </button>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="px-4 py-6 text-center text-slate-500">No customers found.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="border-t border-slate-200 p-4">
      {{ $customers->links() }}
    </div>
  </div>

  @if ($selectedCustomer)
    <div class="space-y-6">
      <div class="rounded-lg border border-slate-200 bg-white p-6">
        <div class="flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
          <div>
            <h2 class="text-lg font-semibold">{{ $selectedCustomer->name ?? 'Unnamed customer' }}</h2>
            <p class="mt-1 text-sm text-slate-500">Read-only customer profile.</p>
          </div>
          <div class="text-sm text-slate-600">{{ ucfirst($selectedCustomer->status) }}</div>
        </div>

        <div class="mt-6 grid gap-6 lg:grid-cols-3">
          <div>
            <h3 class="text-sm font-semibold text-slate-900">Identity</h3>
            <dl class="mt-3 space-y-2 text-sm">
              <div>
                <dt class="text-slate-500">Name</dt>
                <dd>{{ $selectedCustomer->name ?? '-' }}</dd>
              </div>
              <div>
                <dt class="text-slate-500">Phone</dt>
                <dd>{{ $selectedCustomer->phone ?? '-' }}</dd>
              </div>
              <div>
                <dt class="text-slate-500">Gender</dt>
                <dd>{{ $selectedCustomer->gender ?? '-' }}</dd>
              </div>
              <div>
                <dt class="text-slate-500">Date of birth</dt>
                <dd>{{ $selectedCustomer->dob?->format('Y-m-d') ?? '-' }}</dd>
              </div>
            </dl>
          </div>

          <div>
            <h3 class="text-sm font-semibold text-slate-900">Address</h3>
            <dl class="mt-3 space-y-2 text-sm">
              <div>
                <dt class="text-slate-500">Province</dt>
                <dd>{{ $selectedCustomer->province ?? '-' }}</dd>
              </div>
              <div>
                <dt class="text-slate-500">District</dt>
                <dd>{{ $selectedCustomer->district ?? '-' }}</dd>
              </div>
              <div>
                <dt class="text-slate-500">Village</dt>
                <dd>{{ $selectedCustomer->village ?? '-' }}</dd>
              </div>
            </dl>
          </div>

          <div>
            <h3 class="text-sm font-semibold text-slate-900">Account</h3>
            <dl class="mt-3 space-y-2 text-sm">
              <div>
                <dt class="text-slate-500">Status</dt>
                <dd>{{ ucfirst($selectedCustomer->status) }}</dd>
              </div>
              <div>
                <dt class="text-slate-500">Banned at</dt>
                <dd>{{ $selectedCustomer->banned_at?->format('Y-m-d H:i') ?? '-' }}</dd>
              </div>
              <div>
                <dt class="text-slate-500">Remark</dt>
                <dd>{{ $selectedCustomer->remark ?? '-' }}</dd>
              </div>
            </dl>
          </div>
        </div>
      </div>

      <div class="grid gap-6 xl:grid-cols-2">
        <div class="rounded-lg border border-slate-200 bg-white p-6">
          <h2 class="text-lg font-semibold">Account access</h2>

          @if ($selectedCustomer->banned_at || $selectedCustomer->status !== 'active')
            <p class="mt-2 text-sm text-slate-500">Allow this customer to log in again.</p>
            <button type="button" wire:click="unbanCustomer" class="mt-6 rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white">
              Unban customer
            </button>
          @else
            <form wire:submit="banCustomer" class="mt-6 space-y-4">
              <div>
                <label for="banRemark" class="text-sm font-medium text-slate-700">Ban remark</label>
                <textarea id="banRemark" rows="3" wire:model="banRemark" class="mt-1 w-full rounded-lg border-slate-300"></textarea>
                @error('banRemark') <div class="mt-1 text-sm text-red-700">{{ $message }}</div> @enderror
              </div>

              <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white">
                Ban customer
              </button>
            </form>
          @endif
        </div>

        <div class="overflow-hidden rounded-lg border border-slate-200 bg-white">
          <div class="border-b border-slate-200 p-6">
            <h2 class="text-lg font-semibold">Orders</h2>
          </div>

          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
              <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                <tr>
                  <th class="px-4 py-3">Order</th>
                  <th class="px-4 py-3">Date</th>
                  <th class="px-4 py-3">Payment</th>
                  <th class="px-4 py-3">Shipping</th>
                  <th class="px-4 py-3">Amount</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-200">
                @forelse ($customerOrders as $order)
                  <tr>
                    <td class="px-4 py-3 font-medium text-slate-900">{{ $order->order_code ?? $order->id }}</td>
                    <td class="px-4 py-3 text-slate-600">{{ $order->created_at?->format('Y-m-d H:i') }}</td>
                    <td class="px-4 py-3 text-slate-600">{{ ucfirst($order->payment_status) }}</td>
                    <td class="px-4 py-3 text-slate-600">{{ $order->shipping_status ? ucfirst($order->shipping_status) : '-' }}</td>
                    <td class="px-4 py-3 text-slate-600">{{ number_format((float) $order->payment_amount, 2) }}</td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="5" class="px-4 py-6 text-center text-slate-500">No orders found for this customer.</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  @endif
</section>

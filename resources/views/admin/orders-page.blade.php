<section class="space-y-8">
  <div>
    <h1 class="text-2xl font-semibold">Orders</h1>
    <p class="mt-2 text-sm text-slate-500">Review orders, shipping updates, invoice webhooks, cancellations, and manual refunds.</p>
  </div>

  <div class="rounded-lg border border-slate-200 bg-white p-6">
    <div class="grid gap-4 lg:grid-cols-5">
      <div class="lg:col-span-2">
        <label for="search" class="text-sm font-medium text-slate-700">Search</label>
        <input id="search" type="search" wire:model.live.debounce.300ms="search" class="mt-1 w-full rounded-lg border-slate-300" placeholder="Order code or phone">
      </div>

      <div>
        <label for="paymentStatusFilter" class="text-sm font-medium text-slate-700">Payment</label>
        <select id="paymentStatusFilter" wire:model.live="paymentStatusFilter" class="mt-1 w-full rounded-lg border-slate-300">
          <option value="">All</option>
          <option value="pending">Pending</option>
          <option value="paid">Paid</option>
          <option value="expired">Expired</option>
          <option value="cancelled">Cancelled</option>
          <option value="refunded">Refunded</option>
        </select>
      </div>

      <div>
        <label for="shippingStatusFilter" class="text-sm font-medium text-slate-700">Shipping</label>
        <select id="shippingStatusFilter" wire:model.live="shippingStatusFilter" class="mt-1 w-full rounded-lg border-slate-300">
          <option value="">All</option>
          <option value="pending">Pending</option>
          <option value="shipping">Shipping</option>
          <option value="completed">Completed</option>
        </select>
      </div>

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

  <div class="overflow-hidden rounded-lg border border-slate-200 bg-white">
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
          <tr>
            <th class="px-4 py-3">Order</th>
            <th class="px-4 py-3">Customer</th>
            <th class="px-4 py-3">Payment</th>
            <th class="px-4 py-3">Shipping</th>
            <th class="px-4 py-3">Amount</th>
            <th class="px-4 py-3">Date</th>
            <th class="px-4 py-3">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-200">
          @forelse ($orders as $order)
            <tr>
              <td class="px-4 py-3 font-medium text-slate-900">{{ $order->order_code ?? $order->id }}</td>
              <td class="px-4 py-3 text-slate-600">
                <div>{{ $order->shipping_name }}</div>
                <div class="text-xs text-slate-500">{{ $order->shipping_phone }}</div>
              </td>
              <td class="px-4 py-3 text-slate-600">{{ ucfirst($order->payment_status) }}</td>
              <td class="px-4 py-3 text-slate-600">{{ $order->shipping_status ? ucfirst($order->shipping_status) : '-' }}</td>
              <td class="px-4 py-3 text-slate-600">{{ number_format((float) $order->payment_amount, 2) }}</td>
              <td class="px-4 py-3 text-slate-600">{{ $order->created_at?->format('Y-m-d H:i') }}</td>
              <td class="px-4 py-3">
                <button type="button" wire:click="selectOrder('{{ $order->id }}')" class="rounded-lg border border-slate-300 px-3 py-2 font-medium text-slate-700">
                  View
                </button>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="px-4 py-6 text-center text-slate-500">No orders found.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="border-t border-slate-200 p-4">
      {{ $orders->links() }}
    </div>
  </div>

  @if ($selectedOrder)
    <div class="space-y-6">
      <div class="rounded-lg border border-slate-200 bg-white p-6">
        <div class="flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
          <div>
            <h2 class="text-lg font-semibold">Order {{ $selectedOrder->order_code ?? $selectedOrder->id }}</h2>
            <p class="mt-1 text-sm text-slate-500">Read-only order content and payment details.</p>
          </div>
          <div class="text-sm text-slate-600">{{ $selectedOrder->created_at?->format('Y-m-d H:i') }}</div>
        </div>

        <div class="mt-6 grid gap-6 lg:grid-cols-3">
          <div>
            <h3 class="text-sm font-semibold text-slate-900">Amounts</h3>
            <dl class="mt-3 space-y-2 text-sm">
              <div class="flex justify-between gap-4">
                <dt class="text-slate-500">Order</dt>
                <dd>{{ number_format((float) $selectedOrder->order_amount, 2) }}</dd>
              </div>
              <div class="flex justify-between gap-4">
                <dt class="text-slate-500">Shipping</dt>
                <dd>{{ number_format((float) $selectedOrder->shipping_amount, 2) }}</dd>
              </div>
              <div class="flex justify-between gap-4">
                <dt class="text-slate-500">Coupon</dt>
                <dd>{{ number_format((float) $selectedOrder->coupon_amount, 2) }}</dd>
              </div>
              <div class="flex justify-between gap-4 font-semibold">
                <dt>Payment</dt>
                <dd>{{ number_format((float) $selectedOrder->payment_amount, 2) }}</dd>
              </div>
            </dl>
          </div>

          <div>
            <h3 class="text-sm font-semibold text-slate-900">Payment</h3>
            <dl class="mt-3 space-y-2 text-sm">
              <div class="flex justify-between gap-4">
                <dt class="text-slate-500">Status</dt>
                <dd>{{ ucfirst($selectedOrder->payment_status) }}</dd>
              </div>
              <div class="flex justify-between gap-4">
                <dt class="text-slate-500">Channel</dt>
                <dd>{{ $selectedOrder->payment_channel ?? '-' }}</dd>
              </div>
              <div class="flex justify-between gap-4">
                <dt class="text-slate-500">Reference</dt>
                <dd>{{ $selectedOrder->payment?->ref ?? '-' }}</dd>
              </div>
            </dl>
          </div>

          <div>
            <h3 class="text-sm font-semibold text-slate-900">Shipping address</h3>
            <dl class="mt-3 space-y-2 text-sm">
              <div>
                <dt class="text-slate-500">Name / phone</dt>
                <dd>{{ $selectedOrder->shipping_name }} · {{ $selectedOrder->shipping_phone }}</dd>
              </div>
              <div>
                <dt class="text-slate-500">Address</dt>
                <dd>{{ $selectedOrder->shipping_province }} / {{ $selectedOrder->shipping_district }} / {{ $selectedOrder->shipping_village }}</dd>
              </div>
              <div>
                <dt class="text-slate-500">Branch</dt>
                <dd>{{ $selectedOrder->shipping_branch_province }} {{ $selectedOrder->shipping_branch_district }} {{ $selectedOrder->shipping_branch_name }}</dd>
              </div>
            </dl>
          </div>
        </div>
      </div>

      <div class="overflow-hidden rounded-lg border border-slate-200 bg-white">
        <div class="border-b border-slate-200 p-6">
          <h2 class="text-lg font-semibold">Items</h2>
        </div>

        <table class="min-w-full divide-y divide-slate-200 text-sm">
          <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
            <tr>
              <th class="px-4 py-3">Product</th>
              <th class="px-4 py-3">Qty</th>
              <th class="px-4 py-3">Price</th>
              <th class="px-4 py-3">Subtotal</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200">
            @foreach ($selectedOrder->details as $detail)
              <tr>
                <td class="px-4 py-3 font-medium text-slate-900">{{ $detail->name ?? $detail->product?->name }}</td>
                <td class="px-4 py-3 text-slate-600">{{ $detail->quantity }}</td>
                <td class="px-4 py-3 text-slate-600">{{ number_format((float) $detail->price, 2) }}</td>
                <td class="px-4 py-3 text-slate-600">{{ number_format((float) $detail->price * $detail->quantity, 2) }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>

      <div class="grid gap-6 xl:grid-cols-2">
        <div class="rounded-lg border border-slate-200 bg-white p-6">
          <h2 class="text-lg font-semibold">Shipping status</h2>

          <form wire:submit="updateShipping" class="mt-6 grid gap-4 md:grid-cols-2">
            <div>
              <label for="shippingStatus" class="text-sm font-medium text-slate-700">Status</label>
              <select id="shippingStatus" wire:model="shippingStatus" class="mt-1 w-full rounded-lg border-slate-300">
                <option value="">Unset</option>
                <option value="pending">Pending</option>
                <option value="shipping">Shipping</option>
                <option value="completed">Completed</option>
              </select>
              @error('shippingStatus') <div class="mt-1 text-sm text-red-700">{{ $message }}</div> @enderror
            </div>

            <div>
              <label for="shippingTrackingNumber" class="text-sm font-medium text-slate-700">Tracking number</label>
              <input id="shippingTrackingNumber" type="text" wire:model="shippingTrackingNumber" class="mt-1 w-full rounded-lg border-slate-300">
              @error('shippingTrackingNumber') <div class="mt-1 text-sm text-red-700">{{ $message }}</div> @enderror
            </div>

            <div class="md:col-span-2">
              <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white">
                Save shipping
              </button>
            </div>
          </form>
        </div>

        <div class="rounded-lg border border-slate-200 bg-white p-6">
          <h2 class="text-lg font-semibold">Invoice webhook</h2>
          <p class="mt-2 text-sm text-slate-500">Resend the signed invoice payload to the shop webhook URL.</p>

          <button type="button" wire:click="resendInvoiceWebhook" class="mt-6 rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white">
            Resend invoice webhook
          </button>
        </div>

        <div class="rounded-lg border border-slate-200 bg-white p-6">
          <h2 class="text-lg font-semibold">Cancel order</h2>

          <form wire:submit="cancelOrder" class="mt-6 space-y-4">
            <div>
              <label for="cancelRemark" class="text-sm font-medium text-slate-700">Remark</label>
              <input id="cancelRemark" type="text" wire:model="cancelRemark" class="mt-1 w-full rounded-lg border-slate-300">
              @error('cancelRemark') <div class="mt-1 text-sm text-red-700">{{ $message }}</div> @enderror
            </div>

            <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white">
              Cancel and restock
            </button>
          </form>
        </div>

        <div class="rounded-lg border border-slate-200 bg-white p-6">
          <h2 class="text-lg font-semibold">Manual refund</h2>

          <form wire:submit="refundOrder" class="mt-6 space-y-4">
            <div>
              <label for="refundReference" class="text-sm font-medium text-slate-700">Bank reference</label>
              <input id="refundReference" type="text" wire:model="refundReference" class="mt-1 w-full rounded-lg border-slate-300">
              @error('refundReference') <div class="mt-1 text-sm text-red-700">{{ $message }}</div> @enderror
            </div>

            <div>
              <label for="refundNote" class="text-sm font-medium text-slate-700">Note</label>
              <textarea id="refundNote" rows="3" wire:model="refundNote" class="mt-1 w-full rounded-lg border-slate-300"></textarea>
              @error('refundNote') <div class="mt-1 text-sm text-red-700">{{ $message }}</div> @enderror
            </div>

            <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white">
              Mark refunded
            </button>
          </form>
        </div>
      </div>
    </div>
  @endif
</section>

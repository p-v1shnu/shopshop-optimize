<section class="space-y-8">
  <div>
    <h1 class="text-2xl font-semibold">Logs</h1>
    <p class="mt-2 text-sm text-slate-500">Browse central webhook, shipping, and OTP logs.</p>
  </div>

  <div class="rounded-lg border border-slate-200 bg-white p-6">
    <div class="grid gap-4 lg:grid-cols-5">
      <div>
        <label for="logType" class="text-sm font-medium text-slate-700">Log type</label>
        <select id="logType" wire:model.live="logType" class="mt-1 w-full rounded-lg border-slate-300">
          <option value="webhook">Webhook logs</option>
          <option value="shipping">Shipping logs</option>
          <option value="otp">OTP logs</option>
        </select>
      </div>

      <div class="lg:col-span-2">
        <label for="search" class="text-sm font-medium text-slate-700">Search</label>
        <input id="search" type="search" wire:model.live.debounce.300ms="search" class="mt-1 w-full rounded-lg border-slate-300" placeholder="Type, provider, reference, phone, message, model">
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
        @if ($logType === 'shipping')
          <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
            <tr>
              <th class="px-4 py-3">Date</th>
              <th class="px-4 py-3">Provider</th>
              <th class="px-4 py-3">Type</th>
              <th class="px-4 py-3">Reference</th>
              <th class="px-4 py-3">Data</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200">
            @forelse ($logs as $log)
              <tr>
                <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $log->created_at?->format('Y-m-d H:i') }}</td>
                <td class="px-4 py-3 font-medium text-slate-900">{{ $log->provider }}</td>
                <td class="px-4 py-3 text-slate-600">{{ $log->type }}</td>
                <td class="px-4 py-3 text-slate-600">{{ $log->provider_reference }}</td>
                <td class="px-4 py-3">
                  <details>
                    <summary class="cursor-pointer font-medium text-slate-700">View JSON</summary>
                    <pre class="mt-3 max-h-96 overflow-auto rounded bg-slate-950 p-4 text-xs text-slate-100">{{ $this->formatJson($log->data) }}</pre>
                  </details>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="5" class="px-4 py-6 text-center text-slate-500">No logs found.</td>
              </tr>
            @endforelse
          </tbody>
        @elseif ($logType === 'otp')
          <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
            <tr>
              <th class="px-4 py-3">Date</th>
              <th class="px-4 py-3">Provider</th>
              <th class="px-4 py-3">MSISDN</th>
              <th class="px-4 py-3">Reference</th>
              <th class="px-4 py-3">OTP</th>
              <th class="px-4 py-3">Data</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200">
            @forelse ($logs as $log)
              <tr>
                <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $log->created_at?->format('Y-m-d H:i') }}</td>
                <td class="px-4 py-3 font-medium text-slate-900">{{ $log->provider }}</td>
                <td class="px-4 py-3 text-slate-600">{{ $log->msisdn }}</td>
                <td class="px-4 py-3 text-slate-600">{{ $log->provider_reference }}</td>
                <td class="px-4 py-3 text-slate-600">***</td>
                <td class="px-4 py-3">
                  <details>
                    <summary class="cursor-pointer font-medium text-slate-700">View JSON</summary>
                    <pre class="mt-3 max-h-96 overflow-auto rounded bg-slate-950 p-4 text-xs text-slate-100">{{ $this->formatJson($this->maskedOtpData($log)) }}</pre>
                  </details>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="6" class="px-4 py-6 text-center text-slate-500">No logs found.</td>
              </tr>
            @endforelse
          </tbody>
        @else
          <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
            <tr>
              <th class="px-4 py-3">Date</th>
              <th class="px-4 py-3">Type</th>
              <th class="px-4 py-3">Message</th>
              <th class="px-4 py-3">Model</th>
              <th class="px-4 py-3">Detail</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200">
            @forelse ($logs as $log)
              <tr>
                <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $log->created_at?->format('Y-m-d H:i') }}</td>
                <td class="px-4 py-3 font-medium text-slate-900">{{ $log->type }}</td>
                <td class="px-4 py-3 text-slate-600">{{ $log->message }}</td>
                <td class="px-4 py-3 text-slate-600">{{ $log->model }} @if($log->model_id) #{{ $log->model_id }} @endif</td>
                <td class="px-4 py-3">
                  <details>
                    <summary class="cursor-pointer font-medium text-slate-700">View JSON</summary>
                    <pre class="mt-3 max-h-96 overflow-auto rounded bg-slate-950 p-4 text-xs text-slate-100">{{ $this->formatJson($log->detail) }}</pre>
                  </details>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="5" class="px-4 py-6 text-center text-slate-500">No logs found.</td>
              </tr>
            @endforelse
          </tbody>
        @endif
      </table>
    </div>

    <div class="border-t border-slate-200 p-4">
      {{ $logs->links() }}
    </div>
  </div>
</section>

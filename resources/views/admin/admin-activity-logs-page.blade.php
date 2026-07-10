<section class="space-y-8">
  <div>
    <h1 class="text-2xl font-semibold">Admin audit log</h1>
    <p class="mt-2 text-sm text-slate-500">Review admin actions across the platform.</p>
  </div>

  <div class="rounded-lg border border-slate-200 bg-white p-6">
    <div class="grid gap-4 lg:grid-cols-4">
      <div>
        <label for="adminId" class="text-sm font-medium text-slate-700">Admin</label>
        <select id="adminId" wire:model.live="adminId" class="mt-1 w-full rounded-lg border-slate-300">
          <option value="">All admins</option>
          @foreach ($admins as $admin)
            <option value="{{ $admin->id }}">{{ $admin->email }}</option>
          @endforeach
        </select>
      </div>

      <div>
        <label for="action" class="text-sm font-medium text-slate-700">Action</label>
        <select id="action" wire:model.live="action" class="mt-1 w-full rounded-lg border-slate-300">
          <option value="">All actions</option>
          @foreach ($actions as $actionOption)
            <option value="{{ $actionOption }}">{{ $actionOption }}</option>
          @endforeach
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
            <th class="px-4 py-3">Date</th>
            <th class="px-4 py-3">Admin</th>
            <th class="px-4 py-3">Tenant</th>
            <th class="px-4 py-3">Action</th>
            <th class="px-4 py-3">Subject</th>
            <th class="px-4 py-3">Detail</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-200">
          @forelse ($activityLogs as $log)
            <tr>
              <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $log->created_at?->format('Y-m-d H:i') }}</td>
              <td class="px-4 py-3">
                <div class="font-medium text-slate-900">{{ $log->admin?->email ?? 'Deleted admin' }}</div>
                @if ($log->admin?->name)
                  <div class="text-xs text-slate-500">{{ $log->admin->name }}</div>
                @endif
              </td>
              <td class="px-4 py-3 text-slate-600">{{ $log->tenant_id ?? '-' }}</td>
              <td class="px-4 py-3 font-medium text-slate-900">{{ $log->action }}</td>
              <td class="px-4 py-3 text-slate-600">
                @if ($log->subject_type)
                  <div>{{ class_basename($log->subject_type) }}</div>
                  <div class="text-xs text-slate-500">#{{ $log->subject_id }}</div>
                @else
                  -
                @endif
              </td>
              <td class="px-4 py-3">
                <details>
                  <summary class="cursor-pointer font-medium text-slate-700">View JSON</summary>
                  <pre class="mt-3 max-h-96 overflow-auto rounded bg-slate-950 p-4 text-xs text-slate-100">{{ $this->formatJson($log->detail) }}</pre>
                </details>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="px-4 py-6 text-center text-slate-500">No activity logs found.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="border-t border-slate-200 p-4">
      {{ $activityLogs->links() }}
    </div>
  </div>
</section>

<section class="space-y-8">
  <div>
    <h1 class="text-2xl font-semibold">Admin accounts</h1>
    <p class="mt-2 text-sm text-slate-500">Create and manage backoffice access for platform and shop admins.</p>
  </div>

  <div class="rounded-lg border border-slate-200 bg-white p-6">
    <h2 class="text-lg font-semibold">Create admin</h2>

    <form wire:submit="createAdmin" class="mt-6 grid gap-4 lg:grid-cols-2">
      <div>
        <label for="createName" class="text-sm font-medium text-slate-700">Name</label>
        <input id="createName" type="text" wire:model="createName" class="mt-1 w-full rounded-lg border-slate-300">
        @error('createName') <div class="mt-1 text-sm text-red-700">{{ $message }}</div> @enderror
      </div>

      <div>
        <label for="createEmail" class="text-sm font-medium text-slate-700">Email</label>
        <input id="createEmail" type="email" wire:model="createEmail" class="mt-1 w-full rounded-lg border-slate-300">
        @error('createEmail') <div class="mt-1 text-sm text-red-700">{{ $message }}</div> @enderror
      </div>

      <div>
        <label for="createRole" class="text-sm font-medium text-slate-700">Role</label>
        <select id="createRole" wire:model.live="createRole" class="mt-1 w-full rounded-lg border-slate-300">
          <option value="shop">Shop</option>
          <option value="super">Super</option>
        </select>
        @error('createRole') <div class="mt-1 text-sm text-red-700">{{ $message }}</div> @enderror
      </div>

      <div>
        <label for="createTenantId" class="text-sm font-medium text-slate-700">Shop</label>
        <select id="createTenantId" wire:model="createTenantId" class="mt-1 w-full rounded-lg border-slate-300" @disabled($createRole === 'super')>
          <option value="">Select a shop</option>
          @foreach ($tenants as $tenant)
            <option value="{{ $tenant->id }}">{{ $tenant->name }}</option>
          @endforeach
        </select>
        @error('createTenantId') <div class="mt-1 text-sm text-red-700">{{ $message }}</div> @enderror
      </div>

      <div>
        <label for="createPassword" class="text-sm font-medium text-slate-700">Initial password</label>
        <input id="createPassword" type="password" wire:model="createPassword" class="mt-1 w-full rounded-lg border-slate-300">
        @error('createPassword') <div class="mt-1 text-sm text-red-700">{{ $message }}</div> @enderror
      </div>

      <div class="flex items-end">
        <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white">
          Create admin
        </button>
      </div>
    </form>
  </div>

  <div class="overflow-hidden rounded-lg border border-slate-200 bg-white">
    <div class="border-b border-slate-200 p-6">
      <h2 class="text-lg font-semibold">All admins</h2>
    </div>

    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
          <tr>
            <th class="px-4 py-3">Name</th>
            <th class="px-4 py-3">Email</th>
            <th class="px-4 py-3">Role</th>
            <th class="px-4 py-3">Shop</th>
            <th class="px-4 py-3">Status</th>
            <th class="px-4 py-3">Reset password</th>
            <th class="px-4 py-3">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-200">
          @foreach ($admins as $admin)
            <tr>
              <td class="px-4 py-3 font-medium text-slate-900">{{ $admin->name }}</td>
              <td class="px-4 py-3 text-slate-600">{{ $admin->email }}</td>
              <td class="px-4 py-3 text-slate-600">{{ ucfirst($admin->role) }}</td>
              <td class="px-4 py-3 text-slate-600">{{ $admin->tenant?->name ?? '-' }}</td>
              <td class="px-4 py-3 text-slate-600">{{ ucfirst($admin->status) }}</td>
              <td class="px-4 py-3">
                <div class="flex min-w-64 gap-2">
                  <input type="password" wire:model="resetPasswordValue" class="w-full rounded-lg border-slate-300 text-sm" placeholder="New password">
                  <button type="button" wire:click="$set('resetAdminId', {{ $admin->id }}); resetPassword()" class="rounded-lg border border-slate-300 px-3 py-2 font-medium text-slate-700">
                    Reset
                  </button>
                </div>
                @if ($resetAdminId === $admin->id)
                  @error('resetPasswordValue') <div class="mt-1 text-sm text-red-700">{{ $message }}</div> @enderror
                @endif
              </td>
              <td class="px-4 py-3">
                @if ($admin->status === 'active')
                  <button type="button" wire:click="setStatus({{ $admin->id }}, 'inactive')" class="rounded-lg border border-slate-300 px-3 py-2 font-medium text-slate-700">
                    Disable
                  </button>
                @else
                  <button type="button" wire:click="setStatus({{ $admin->id }}, 'active')" class="rounded-lg border border-slate-300 px-3 py-2 font-medium text-slate-700">
                    Enable
                  </button>
                @endif
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
</section>

<section class="space-y-8">
  <div>
    <h1 class="text-2xl font-semibold">Brands</h1>
    <p class="mt-2 text-sm text-slate-500">Create tenants, manage domains, and edit storefront configuration.</p>
  </div>

  <form wire:submit="createTenant" class="space-y-6 rounded-lg border border-slate-200 bg-white p-6">
    <h2 class="text-lg font-semibold">Create brand</h2>

    <div class="grid gap-4 lg:grid-cols-4">
      <div>
        <label for="createId" class="text-sm font-medium text-slate-700">Tenant ID</label>
        <input id="createId" type="text" wire:model="createId" class="mt-1 w-full rounded-lg border-slate-300" placeholder="new-brand">
        @error('createId') <div class="mt-1 text-sm text-red-700">{{ $message }}</div> @enderror
      </div>

      <div>
        <label for="createName" class="text-sm font-medium text-slate-700">Name</label>
        <input id="createName" type="text" wire:model="createName" class="mt-1 w-full rounded-lg border-slate-300">
        @error('createName') <div class="mt-1 text-sm text-red-700">{{ $message }}</div> @enderror
      </div>

      <div>
        <label for="createStatus" class="text-sm font-medium text-slate-700">Status</label>
        <select id="createStatus" wire:model="createStatus" class="mt-1 w-full rounded-lg border-slate-300">
          <option value="active">Active</option>
          <option value="inactive">Inactive</option>
        </select>
        @error('createStatus') <div class="mt-1 text-sm text-red-700">{{ $message }}</div> @enderror
      </div>

      <div>
        <label for="createDomain" class="text-sm font-medium text-slate-700">Domain</label>
        <input id="createDomain" type="text" wire:model="createDomain" class="mt-1 w-full rounded-lg border-slate-300" placeholder="new-brand.shopshop.test">
        @error('createDomain') <div class="mt-1 text-sm text-red-700">{{ $message }}</div> @enderror
      </div>
    </div>

    <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white">
      Create brand
    </button>
  </form>

  <div class="overflow-hidden rounded-lg border border-slate-200 bg-white">
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
          <tr>
            <th class="px-4 py-3">ID</th>
            <th class="px-4 py-3">Name</th>
            <th class="px-4 py-3">Status</th>
            <th class="px-4 py-3">Domains</th>
            <th class="px-4 py-3">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-200">
          @forelse ($tenants as $tenant)
            <tr>
              <td class="px-4 py-3 font-medium text-slate-900">{{ $tenant->id }}</td>
              <td class="px-4 py-3 text-slate-600">{{ $tenant->name }}</td>
              <td class="px-4 py-3 text-slate-600">{{ ucfirst($tenant->status) }}</td>
              <td class="px-4 py-3 text-slate-600">
                {{ $tenant->domains->pluck('domain')->join(', ') }}
              </td>
              <td class="px-4 py-3">
                <button type="button" wire:click="edit('{{ $tenant->id }}')" class="rounded-lg border border-slate-300 px-3 py-2 font-medium text-slate-700">
                  Edit
                </button>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="5" class="px-4 py-6 text-center text-slate-500">No brands found.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="border-t border-slate-200 p-4">
      {{ $tenants->links() }}
    </div>
  </div>

  @if ($isEditing && $selectedTenant)
    <div class="space-y-6">
      <form wire:submit="save" class="space-y-6 rounded-lg border border-slate-200 bg-white p-6">
        <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
          <h2 class="text-lg font-semibold">Edit {{ $selectedTenant->id }}</h2>
          <button type="button" wire:click="cancelEdit" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700">
            Cancel
          </button>
        </div>

        <div class="grid gap-4 lg:grid-cols-3">
          <div>
            <label for="name" class="text-sm font-medium text-slate-700">Name</label>
            <input id="name" type="text" wire:model="name" class="mt-1 w-full rounded-lg border-slate-300">
            @error('name') <div class="mt-1 text-sm text-red-700">{{ $message }}</div> @enderror
          </div>

          <div>
            <label for="status" class="text-sm font-medium text-slate-700">Status</label>
            <select id="status" wire:model="status" class="mt-1 w-full rounded-lg border-slate-300">
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
            </select>
            @error('status') <div class="mt-1 text-sm text-red-700">{{ $message }}</div> @enderror
          </div>

          <div>
            <label for="siteLogoUpload" class="text-sm font-medium text-slate-700">Site logo</label>
            <input id="siteLogoUpload" type="file" wire:model="siteLogoUpload" class="mt-1 w-full rounded-lg border border-slate-300 p-2 text-sm">
            @if ($siteLogoUrl)
              <div class="mt-1 truncate text-xs text-slate-500">{{ $siteLogoUrl }}</div>
            @endif
            @error('siteLogoUpload') <div class="mt-1 text-sm text-red-700">{{ $message }}</div> @enderror
          </div>

          <div>
            <label for="facebookName" class="text-sm font-medium text-slate-700">Facebook name</label>
            <input id="facebookName" type="text" wire:model="facebookName" class="mt-1 w-full rounded-lg border-slate-300">
            @error('facebookName') <div class="mt-1 text-sm text-red-700">{{ $message }}</div> @enderror
          </div>

          <div>
            <label for="facebookUrl" class="text-sm font-medium text-slate-700">Facebook URL</label>
            <input id="facebookUrl" type="url" wire:model="facebookUrl" class="mt-1 w-full rounded-lg border-slate-300">
            @error('facebookUrl') <div class="mt-1 text-sm text-red-700">{{ $message }}</div> @enderror
          </div>

          <div>
            <label for="facebookCoverUrl" class="text-sm font-medium text-slate-700">Facebook cover URL</label>
            <input id="facebookCoverUrl" type="url" wire:model="facebookCoverUrl" class="mt-1 w-full rounded-lg border-slate-300">
            @error('facebookCoverUrl') <div class="mt-1 text-sm text-red-700">{{ $message }}</div> @enderror
          </div>

          <div>
            <label for="googleTagManagerId" class="text-sm font-medium text-slate-700">Google Tag Manager ID</label>
            <input id="googleTagManagerId" type="text" wire:model="googleTagManagerId" class="mt-1 w-full rounded-lg border-slate-300">
            @error('googleTagManagerId') <div class="mt-1 text-sm text-red-700">{{ $message }}</div> @enderror
          </div>

          <div>
            <label for="googleAnalyticsId" class="text-sm font-medium text-slate-700">Google Analytics ID</label>
            <input id="googleAnalyticsId" type="text" wire:model="googleAnalyticsId" class="mt-1 w-full rounded-lg border-slate-300">
            @error('googleAnalyticsId') <div class="mt-1 text-sm text-red-700">{{ $message }}</div> @enderror
          </div>

          <div class="flex items-center gap-2 pt-7">
            <input id="maintenanceMode" type="checkbox" wire:model="maintenanceMode" class="rounded border-slate-300">
            <label for="maintenanceMode" class="text-sm font-medium text-slate-700">Maintenance mode</label>
            @error('maintenanceMode') <div class="mt-1 text-sm text-red-700">{{ $message }}</div> @enderror
          </div>

          <div>
            <label for="shippingChannels" class="text-sm font-medium text-slate-700">Shipping channels</label>
            <select id="shippingChannels" multiple wire:model="shippingChannels" class="mt-1 w-full rounded-lg border-slate-300">
              <option value="hal">HAL</option>
              <option value="seller">Seller</option>
              <option value="none">None</option>
            </select>
            @error('shippingChannels') <div class="mt-1 text-sm text-red-700">{{ $message }}</div> @enderror
            @error('shippingChannels.*') <div class="mt-1 text-sm text-red-700">{{ $message }}</div> @enderror
          </div>

          <div>
            <label for="allowProvinceIds" class="text-sm font-medium text-slate-700">Allowed provinces</label>
            <select id="allowProvinceIds" multiple wire:model="allowProvinceIds" class="mt-1 w-full rounded-lg border-slate-300">
              @foreach ($provinces as $province)
                <option value="{{ $province['id'] }}">{{ $province['id'] }} - {{ $province['name'] }}</option>
              @endforeach
            </select>
            @error('allowProvinceIds') <div class="mt-1 text-sm text-red-700">{{ $message }}</div> @enderror
            @error('allowProvinceIds.*') <div class="mt-1 text-sm text-red-700">{{ $message }}</div> @enderror
          </div>

          <div>
            <label for="deliveryContactPhone" class="text-sm font-medium text-slate-700">Delivery contact phones</label>
            <textarea id="deliveryContactPhone" rows="4" wire:model="deliveryContactPhone" class="mt-1 w-full rounded-lg border-slate-300"></textarea>
            @error('deliveryContactPhone') <div class="mt-1 text-sm text-red-700">{{ $message }}</div> @enderror
          </div>

          <div>
            <label for="orderInvoiceWebhookUrl" class="text-sm font-medium text-slate-700">Invoice webhook URL</label>
            <input id="orderInvoiceWebhookUrl" type="url" wire:model="orderInvoiceWebhookUrl" class="mt-1 w-full rounded-lg border-slate-300">
            @error('orderInvoiceWebhookUrl') <div class="mt-1 text-sm text-red-700">{{ $message }}</div> @enderror
          </div>

          <div>
            <label for="supportContactPhone" class="text-sm font-medium text-slate-700">Support contact phone</label>
            <input id="supportContactPhone" type="text" wire:model="supportContactPhone" class="mt-1 w-full rounded-lg border-slate-300">
            @error('supportContactPhone') <div class="mt-1 text-sm text-red-700">{{ $message }}</div> @enderror
          </div>

          <div>
            <label for="otpSiteName" class="text-sm font-medium text-slate-700">OTP site name</label>
            <input id="otpSiteName" type="text" wire:model="otpSiteName" class="mt-1 w-full rounded-lg border-slate-300">
            @error('otpSiteName') <div class="mt-1 text-sm text-red-700">{{ $message }}</div> @enderror
          </div>

          <div>
            <label for="contactUrl" class="text-sm font-medium text-slate-700">Contact URL</label>
            <input id="contactUrl" type="url" wire:model="contactUrl" class="mt-1 w-full rounded-lg border-slate-300">
            @error('contactUrl') <div class="mt-1 text-sm text-red-700">{{ $message }}</div> @enderror
          </div>

          <div>
            <label for="footerMoreInfoText" class="text-sm font-medium text-slate-700">Footer more info text</label>
            <input id="footerMoreInfoText" type="text" wire:model="footerMoreInfoText" class="mt-1 w-full rounded-lg border-slate-300">
            @error('footerMoreInfoText') <div class="mt-1 text-sm text-red-700">{{ $message }}</div> @enderror
          </div>

          <div>
            <label for="footerMoreInfoLink" class="text-sm font-medium text-slate-700">Footer more info link</label>
            <input id="footerMoreInfoLink" type="url" wire:model="footerMoreInfoLink" class="mt-1 w-full rounded-lg border-slate-300">
            @error('footerMoreInfoLink') <div class="mt-1 text-sm text-red-700">{{ $message }}</div> @enderror
          </div>

          <div>
            <label for="latitude" class="text-sm font-medium text-slate-700">Latitude</label>
            <input id="latitude" type="text" wire:model="latitude" class="mt-1 w-full rounded-lg border-slate-300">
            @error('latitude') <div class="mt-1 text-sm text-red-700">{{ $message }}</div> @enderror
          </div>

          <div>
            <label for="longitude" class="text-sm font-medium text-slate-700">Longitude</label>
            <input id="longitude" type="text" wire:model="longitude" class="mt-1 w-full rounded-lg border-slate-300">
            @error('longitude') <div class="mt-1 text-sm text-red-700">{{ $message }}</div> @enderror
          </div>

          <div>
            <label for="title" class="text-sm font-medium text-slate-700">Title</label>
            <input id="title" type="text" wire:model="title" class="mt-1 w-full rounded-lg border-slate-300">
            @error('title') <div class="mt-1 text-sm text-red-700">{{ $message }}</div> @enderror
          </div>

          <div class="lg:col-span-3">
            <label for="noShippingInstructionText" class="text-sm font-medium text-slate-700">No-shipping instruction text</label>
            <textarea id="noShippingInstructionText" rows="3" wire:model="noShippingInstructionText" class="mt-1 w-full rounded-lg border-slate-300"></textarea>
            @error('noShippingInstructionText') <div class="mt-1 text-sm text-red-700">{{ $message }}</div> @enderror
          </div>

          <div class="lg:col-span-3">
            <label for="noShippingPaidText" class="text-sm font-medium text-slate-700">No-shipping paid text</label>
            <textarea id="noShippingPaidText" rows="3" wire:model="noShippingPaidText" class="mt-1 w-full rounded-lg border-slate-300"></textarea>
            @error('noShippingPaidText') <div class="mt-1 text-sm text-red-700">{{ $message }}</div> @enderror
          </div>

          <div class="lg:col-span-3">
            <label for="headHtml" class="text-sm font-medium text-slate-700">Head HTML</label>
            <textarea id="headHtml" rows="4" wire:model="headHtml" class="mt-1 w-full rounded-lg border-slate-300"></textarea>
            @error('headHtml') <div class="mt-1 text-sm text-red-700">{{ $message }}</div> @enderror
          </div>
        </div>

        <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white">
          Save brand config
        </button>
      </form>

      <div class="rounded-lg border border-slate-200 bg-white p-6">
        <h2 class="text-lg font-semibold">Domains</h2>

        <form wire:submit="addDomain" class="mt-4 flex flex-col gap-3 md:flex-row">
          <div class="flex-1">
            <label for="newDomain" class="sr-only">New domain</label>
            <input id="newDomain" type="text" wire:model="newDomain" class="w-full rounded-lg border-slate-300" placeholder="brand.shopshop.test">
            @error('newDomain') <div class="mt-1 text-sm text-red-700">{{ $message }}</div> @enderror
          </div>
          <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white">
            Add domain
          </button>
        </form>

        <div class="mt-6 overflow-x-auto">
          <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
              <tr>
                <th class="px-4 py-3">Domain</th>
                <th class="px-4 py-3">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
              @foreach ($selectedTenant->domains as $domain)
                <tr>
                  <td class="px-4 py-3 font-medium text-slate-900">{{ $domain->domain }}</td>
                  <td class="px-4 py-3">
                    <button type="button" wire:click="removeDomain({{ $domain->id }})" wire:confirm="Remove this domain?" class="rounded-lg border border-red-200 px-3 py-2 font-medium text-red-700">
                      Remove
                    </button>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>
  @endif
</section>

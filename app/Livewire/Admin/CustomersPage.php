<?php

namespace App\Livewire\Admin;

use App\Models\ShopOrder;
use App\Models\User;
use App\Support\AdminTenantScope;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class CustomersPage extends Component
{
    use WithPagination;

    public string $tenantId;

    public string $search = '';

    public ?int $selectedCustomerId = null;

    public string $banRemark = '';

    public function mount(AdminTenantScope $tenantScope): void
    {
        $admin = Auth::guard('admin')->user();
        $this->tenantId = $admin->isShop()
            ? $admin->tenant_id
            : (string) $tenantScope->currentTenantId();

        abort_if(blank($this->tenantId), 404);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function selectCustomer(int $customerId): void
    {
        $customer = $this->findScopedCustomer($customerId);

        $this->selectedCustomerId = $customer->id;
        $this->banRemark = '';
        $this->resetValidation();
    }

    public function banCustomer(?int $customerId = null): void
    {
        $targetCustomerId = $customerId ?? $this->selectedCustomerId;

        abort_if(! $targetCustomerId, 404);

        $validated = $this->validate([
            'banRemark' => ['required', 'string', 'max:1000'],
        ]);

        $this->findScopedCustomer($targetCustomerId)->update([
            'status' => 'inactive',
            'banned_at' => now(),
            'remark' => $this->banRemark($validated['banRemark']),
        ]);

        $this->banRemark = '';
    }

    public function unbanCustomer(?int $customerId = null): void
    {
        $targetCustomerId = $customerId ?? $this->selectedCustomerId;

        abort_if(! $targetCustomerId, 404);

        $this->findScopedCustomer($targetCustomerId)->update([
            'status' => 'active',
            'banned_at' => null,
        ]);
    }

    public function render()
    {
        return view('admin.customers-page', [
            'customers' => $this->customersQuery()->paginate(10),
            'selectedCustomer' => $this->selectedCustomer(),
            'customerOrders' => $this->customerOrders(),
        ])->layout('admin.layout')
            ->title('Customers');
    }

    private function customersQuery()
    {
        return User::query()
            ->where('tenant_id', $this->tenantId)
            ->where('role', 'user')
            ->when($this->search !== '', function ($query): void {
                $search = '%'.$this->search.'%';
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', $search)
                        ->orWhere('phone', 'like', $search);
                });
            })
            ->orderBy('name')
            ->orderBy('phone');
    }

    private function selectedCustomer(): ?User
    {
        if (! $this->selectedCustomerId) {
            return null;
        }

        return $this->findScopedCustomer($this->selectedCustomerId);
    }

    private function customerOrders()
    {
        if (! $this->selectedCustomerId) {
            return collect();
        }

        return ShopOrder::query()
            ->where('tenant_id', $this->tenantId)
            ->where('user_id', $this->selectedCustomerId)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(20)
            ->get();
    }

    private function findScopedCustomer(int $customerId): User
    {
        $customer = User::query()
            ->where('tenant_id', $this->tenantId)
            ->where('role', 'user')
            ->whereKey($customerId)
            ->first();

        abort_if(! $customer, 404);

        return $customer;
    }

    private function banRemark(string $remark): string
    {
        $admin = Auth::guard('admin')->user();

        return sprintf(
            'Banned by %s (%s) at %s: %s',
            $admin->email,
            $admin->role,
            now()->toDateTimeString(),
            $remark
        );
    }
}

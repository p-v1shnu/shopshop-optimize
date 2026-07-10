<?php

namespace App\Livewire\Admin;

use App\Models\ShopProduct;
use App\Support\AdminTenantScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class SearchAnalyticsPage extends Component
{
    use WithPagination;

    public string $tenantId;

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    public function mount(AdminTenantScope $tenantScope): void
    {
        $admin = Auth::guard('admin')->user();
        $this->tenantId = $admin->isShop()
            ? $admin->tenant_id
            : (string) $tenantScope->currentTenantId();

        abort_if(blank($this->tenantId), 404);
    }

    public function updatedDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatedDateTo(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        return view('admin.search-analytics-page', [
            'searchTerms' => $this->searchTermsQuery()->paginate(10),
            'topProducts' => $this->topProducts(),
        ])->layout('admin.layout')
            ->title('Search Analytics');
    }

    private function searchTermsQuery()
    {
        return DB::table('shop_user_searches')
            ->select('search_term', DB::raw('COUNT(*) as search_count'), DB::raw('MAX(created_at) as last_searched_at'))
            ->where('tenant_id', $this->tenantId)
            ->when($this->dateFrom, fn ($query) => $query->where('created_at', '>=', $this->dateFrom.' 00:00:00'))
            ->when($this->dateTo, fn ($query) => $query->where('created_at', '<=', $this->dateTo.' 23:59:59'))
            ->groupBy('search_term')
            ->orderByDesc('search_count')
            ->orderBy('search_term');
    }

    private function topProducts()
    {
        return ShopProduct::query()
            ->where('tenant_id', $this->tenantId)
            ->orderByDesc('total_search')
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name', 'sku', 'status', 'total_search', 'available_quantity']);
    }
}

<?php

namespace App\Livewire\Admin;

use App\Models\ShopOrder;
use App\Models\ShopProduct;
use App\Support\AdminTenantScope;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class DashboardPage extends Component
{
    public const LOW_STOCK_THRESHOLD = 5;

    public ?string $tenantId = null;

    public function mount(AdminTenantScope $tenantScope): void
    {
        $admin = Auth::guard('admin')->user();
        $this->tenantId = $admin->isShop()
            ? $admin->tenant_id
            : $tenantScope->currentTenantId();
    }

    public function render()
    {
        return view('admin.dashboard-page', [
            'metrics' => $this->metrics(),
            'lowStockThreshold' => self::LOW_STOCK_THRESHOLD,
        ])
            ->layout('admin.layout')
            ->title('Admin Dashboard');
    }

    private function metrics(): ?array
    {
        if (blank($this->tenantId)) {
            return null;
        }

        $now = Carbon::now(config('app.timezone'));
        $todayStart = $now->copy()->startOfDay();
        $todayEnd = $now->copy()->endOfDay();
        $monthStart = $now->copy()->startOfMonth();
        $monthEnd = $now->copy()->endOfMonth();

        $today = $this->paidOrdersBetween($todayStart, $todayEnd);
        $month = $this->paidOrdersBetween($monthStart, $monthEnd);

        return [
            'today_sales' => (float) $today->total,
            'today_orders' => (int) $today->count,
            'month_sales' => (float) $month->total,
            'month_orders' => (int) $month->count,
            'pending_to_ship' => $this->paidOrdersQuery()
                ->where('shipping_status', 'pending')
                ->count(),
            'low_stock_products' => ShopProduct::query()
                ->where('tenant_id', $this->tenantId)
                ->where('available_quantity', '<=', self::LOW_STOCK_THRESHOLD)
                ->count(),
        ];
    }

    private function paidOrdersBetween(Carbon $start, Carbon $end): object
    {
        return $this->paidOrdersQuery()
            ->whereBetween('created_at', [$start->toDateTimeString(), $end->toDateTimeString()])
            ->selectRaw('COALESCE(SUM(payment_amount), 0) as total, COUNT(*) as count')
            ->first();
    }

    private function paidOrdersQuery()
    {
        return ShopOrder::query()
            ->where('tenant_id', $this->tenantId)
            ->where('payment_status', 'paid');
    }
}

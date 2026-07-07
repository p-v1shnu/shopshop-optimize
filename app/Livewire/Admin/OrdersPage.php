<?php

namespace App\Livewire\Admin;

use App\Models\ShopCoupon;
use App\Models\ShopOrder;
use App\Models\ShopOrderLog;
use App\Models\ShopProduct;
use App\Support\AdminTenantScope;
use App\Support\InvoiceWebhookNotifier;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithPagination;

class OrdersPage extends Component
{
    use WithPagination;

    public string $tenantId;

    public string $search = '';

    public string $paymentStatusFilter = '';

    public string $shippingStatusFilter = '';

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    public ?string $selectedOrderId = null;

    public ?string $shippingStatus = null;

    public ?string $shippingTrackingNumber = null;

    public string $cancelRemark = '';

    public string $refundReference = '';

    public ?string $refundNote = null;

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

    public function updatedPaymentStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedShippingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatedDateTo(): void
    {
        $this->resetPage();
    }

    public function selectOrder(string $orderId): void
    {
        $order = $this->findScopedOrder($orderId);

        $this->selectedOrderId = $order->id;
        $this->shippingStatus = $order->shipping_status;
        $this->shippingTrackingNumber = $order->shipping_tracking_number;
        $this->cancelRemark = '';
        $this->refundReference = '';
        $this->refundNote = null;
        $this->resetValidation();
    }

    public function updateShipping(): void
    {
        abort_if(! $this->selectedOrderId, 404);

        $validated = $this->validate([
            'shippingStatus' => ['nullable', Rule::in(['pending', 'shipping', 'completed'])],
            'shippingTrackingNumber' => ['nullable', 'string', 'max:255'],
        ]);

        $this->findScopedOrder($this->selectedOrderId)->update([
            'shipping_status' => $validated['shippingStatus'],
            'shipping_tracking_number' => blank($validated['shippingTrackingNumber'])
                ? null
                : $validated['shippingTrackingNumber'],
        ]);
    }

    public function resendInvoiceWebhook(): void
    {
        abort_if(! $this->selectedOrderId, 404);

        app(InvoiceWebhookNotifier::class)->notify(
            $this->findScopedOrder($this->selectedOrderId)
        );
    }

    public function cancelOrder(): void
    {
        abort_if(! $this->selectedOrderId, 404);

        $validated = $this->validate([
            'cancelRemark' => ['required', 'string', 'max:1000'],
        ]);

        $cancelled = false;

        DB::transaction(function () use ($validated, &$cancelled): void {
            $order = $this->findScopedOrderForUpdate($this->selectedOrderId, ['details', 'coupons']);

            if (! in_array($order->payment_status, ['pending', 'paid'], true)) {
                $this->addError('cancelRemark', 'Only pending or paid orders can be cancelled.');
                return;
            }

            foreach ($order->details as $detail) {
                $product = ShopProduct::query()
                    ->where('tenant_id', $this->tenantId)
                    ->whereKey($detail->shop_product_id)
                    ->first();

                if (! $product) {
                    continue;
                }

                $result = $product->updateProductAvailableQuantity(
                    (int) $detail->quantity,
                    'UPDATE',
                    'Order cancelled: '.$validated['cancelRemark']
                );

                if (! $result['success']) {
                    throw ValidationException::withMessages([
                        'cancelRemark' => $result['message'],
                    ]);
                }
            }

            foreach ($order->coupons as $orderCoupon) {
                ShopCoupon::query()
                    ->where('tenant_id', $this->tenantId)
                    ->whereKey($orderCoupon->shop_coupon_id)
                    ->increment('available_quantity');
            }

            $order->update([
                'payment_status' => 'cancelled',
            ]);

            ShopOrderLog::query()->create([
                'tenant_id' => $order->tenant_id,
                'shop_order_id' => $order->id,
                'type' => 'cancel_order',
                'detail' => [
                    'remark' => $validated['cancelRemark'],
                    'actor' => $this->actorDetail(),
                    'recorded_at' => now()->toIso8601String(),
                ],
            ]);

            $cancelled = true;
        });

        if (! $cancelled) {
            return;
        }

        $this->cancelRemark = '';
    }

    public function refundOrder(): void
    {
        abort_if(! $this->selectedOrderId, 404);

        $validated = $this->validate([
            'refundReference' => ['required', 'string', 'max:255'],
            'refundNote' => ['nullable', 'string', 'max:1000'],
        ]);

        $refunded = false;

        DB::transaction(function () use ($validated, &$refunded): void {
            $order = $this->findScopedOrderForUpdate($this->selectedOrderId);

            if ($order->payment_status !== 'paid') {
                $this->addError('refundReference', 'Only paid orders can be refunded.');
                return;
            }

            $order->update([
                'payment_status' => 'refunded',
            ]);

            ShopOrderLog::query()->create([
                'tenant_id' => $order->tenant_id,
                'shop_order_id' => $order->id,
                'type' => 'refund',
                'detail' => [
                    'reference' => $validated['refundReference'],
                    'note' => blank($validated['refundNote']) ? null : $validated['refundNote'],
                    'actor' => $this->actorDetail(),
                    'recorded_at' => now()->toIso8601String(),
                ],
            ]);

            $refunded = true;
        });

        if (! $refunded) {
            return;
        }

        $this->refundReference = '';
        $this->refundNote = null;
    }

    public function render()
    {
        return view('admin.orders-page', [
            'orders' => $this->ordersQuery()->paginate(10),
            'selectedOrder' => $this->selectedOrder(),
        ])->layout('admin.layout')
            ->title('Orders');
    }

    private function ordersQuery()
    {
        return ShopOrder::query()
            ->where('tenant_id', $this->tenantId)
            ->withCount('details')
            ->when($this->paymentStatusFilter !== '', fn ($query) => $query->where('payment_status', $this->paymentStatusFilter))
            ->when($this->shippingStatusFilter !== '', fn ($query) => $query->where('shipping_status', $this->shippingStatusFilter))
            ->when($this->dateFrom, fn ($query) => $query->where('created_at', '>=', $this->dateFrom.' 00:00:00'))
            ->when($this->dateTo, fn ($query) => $query->where('created_at', '<=', $this->dateTo.' 23:59:59'))
            ->when($this->search !== '', function ($query): void {
                $search = '%'.$this->search.'%';
                $query->where(function ($query) use ($search): void {
                    $query->where('order_code', 'like', $search)
                        ->orWhere('shipping_phone', 'like', $search);
                });
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }

    private function selectedOrder(): ?ShopOrder
    {
        if (! $this->selectedOrderId) {
            return null;
        }

        return $this->findScopedOrder($this->selectedOrderId, [
            'details.product',
            'payment',
            'coupons',
            'logs' => fn ($query) => $query->latest('id')->limit(20),
        ]);
    }

    private function findScopedOrder(string $orderId, array $with = []): ShopOrder
    {
        $order = ShopOrder::query()
            ->where('tenant_id', $this->tenantId)
            ->whereKey($orderId)
            ->with($with)
            ->first();

        abort_if(! $order, 404);

        return $order;
    }

    private function findScopedOrderForUpdate(string $orderId, array $with = []): ShopOrder
    {
        $order = ShopOrder::query()
            ->where('tenant_id', $this->tenantId)
            ->whereKey($orderId)
            ->lockForUpdate()
            ->first();

        abort_if(! $order, 404);

        if ($with !== []) {
            $order->load($with);
        }

        return $order;
    }

    private function actorDetail(): array
    {
        $admin = Auth::guard('admin')->user();

        return [
            'id' => $admin->id,
            'email' => $admin->email,
            'name' => $admin->name,
            'role' => $admin->role,
        ];
    }
}

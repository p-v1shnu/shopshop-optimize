<?php

namespace App\Livewire\Admin;

use App\Models\ShopCoupon;
use App\Models\ShopOrderCoupon;
use App\Models\User;
use App\Support\AdminTenantScope;
use Carbon\CarbonInterface;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class CouponsPage extends Component
{
    use WithPagination;

    public string $tenantId;

    public string $search = '';

    public string $statusFilter = '';

    public string $typeFilter = '';

    public ?int $editingCouponId = null;

    public bool $isEditing = false;

    public string $code = '';

    public string $type = 'fixed';

    public string $amount = '';

    public ?string $startedAt = null;

    public ?string $endedAt = null;

    public int $totalQuantity = 0;

    public int $availableQuantity = 0;

    public int $userDailyLimit = 0;

    public string $minimumOrderAmount = '0';

    public string $status = 'active';

    public ?string $remark = null;

    public ?string $userId = null;

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

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedTypeFilter(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        $this->resetForm();
        $this->isEditing = true;
    }

    public function edit(int $couponId): void
    {
        $coupon = $this->findScopedCoupon($couponId);

        $this->editingCouponId = $coupon->id;
        $this->isEditing = true;
        $this->code = $coupon->code;
        $this->type = $coupon->type;
        $this->amount = (string) $coupon->amount;
        $this->startedAt = $this->formatForInput($coupon->started_at);
        $this->endedAt = $this->formatForInput($coupon->ended_at);
        $this->totalQuantity = $coupon->total_quantity;
        $this->availableQuantity = $coupon->available_quantity;
        $this->userDailyLimit = $coupon->user_daily_limit;
        $this->minimumOrderAmount = (string) $coupon->minimum_order_amount;
        $this->status = $coupon->status;
        $this->remark = $coupon->remark;
        $this->userId = $coupon->user_id === null ? null : (string) $coupon->user_id;
        $this->resetValidation();
    }

    public function cancelEdit(): void
    {
        $this->resetForm();
    }

    public function save(): void
    {
        $validated = $this->validate($this->couponRules());

        $payload = [
            'tenant_id' => $this->tenantId,
            'status' => $validated['status'],
            'user_id' => $this->blankToNull($validated['userId']),
            'code' => $validated['code'],
            'type' => $validated['type'],
            'amount' => $validated['amount'],
            'started_at' => $this->normalizeDateTime($validated['startedAt']),
            'ended_at' => $this->normalizeDateTime($validated['endedAt']),
            'total_quantity' => $validated['totalQuantity'],
            'available_quantity' => $validated['availableQuantity'],
            'user_daily_limit' => $validated['userDailyLimit'],
            'minimum_order_amount' => $validated['minimumOrderAmount'],
            'remark' => $this->blankToNull($validated['remark']),
        ];

        try {
            if ($this->editingCouponId) {
                $this->findScopedCoupon($this->editingCouponId)->update($payload);
            } else {
                ShopCoupon::query()->create($payload);
            }
        } catch (QueryException $exception) {
            if ($this->handleAmountTriggerException($exception)) {
                return;
            }

            throw $exception;
        }

        $this->resetForm();
    }

    public function render()
    {
        return view('admin.coupons-page', [
            'coupons' => $this->couponsQuery()->paginate(10),
            'customers' => $this->customers(),
            'usageHistory' => $this->usageHistory(),
        ])->layout('admin.layout')
            ->title('Coupons');
    }

    private function couponsQuery()
    {
        return ShopCoupon::query()
            ->where('tenant_id', $this->tenantId)
            ->with('user:id,name,phone')
            ->when($this->search !== '', fn ($query) => $query->where('code', 'like', '%'.$this->search.'%'))
            ->when($this->statusFilter !== '', fn ($query) => $query->where('status', $this->statusFilter))
            ->when($this->typeFilter !== '', fn ($query) => $query->where('type', $this->typeFilter))
            ->orderBy('code');
    }

    private function couponRules(): array
    {
        return [
            'code' => [
                'required',
                'string',
                'max:255',
                Rule::unique('shop_coupons', 'code')
                    ->where('tenant_id', $this->tenantId)
                    ->ignore($this->editingCouponId),
            ],
            'type' => ['required', Rule::in(['fixed', 'percentage'])],
            'amount' => [
                'required',
                'numeric',
                'min:0',
                Rule::when($this->type === 'percentage', ['max:100']),
            ],
            'startedAt' => ['nullable', 'date'],
            'endedAt' => ['nullable', 'date', 'after_or_equal:startedAt'],
            'totalQuantity' => ['required', 'integer', 'min:0'],
            'availableQuantity' => ['required', 'integer', 'min:0'],
            'userDailyLimit' => ['required', 'integer', 'min:0'],
            'minimumOrderAmount' => ['required', 'numeric', 'min:0'],
            'status' => ['required', Rule::in(['active', 'inactive', 'expired', 'sold_out'])],
            'remark' => ['nullable', 'string'],
            'userId' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')
                    ->where('tenant_id', $this->tenantId)
                    ->where('role', 'user'),
            ],
        ];
    }

    private function customers()
    {
        return User::query()
            ->where('tenant_id', $this->tenantId)
            ->where('role', 'user')
            ->orderBy('name')
            ->orderBy('phone')
            ->get(['id', 'name', 'phone']);
    }

    private function usageHistory()
    {
        if (! $this->editingCouponId) {
            return collect();
        }

        return ShopOrderCoupon::query()
            ->where('tenant_id', $this->tenantId)
            ->where('shop_coupon_id', $this->editingCouponId)
            ->with(['shopOrder:id,order_code', 'user:id,name,phone'])
            ->orderByDesc('id')
            ->limit(20)
            ->get();
    }

    private function findScopedCoupon(int $couponId): ShopCoupon
    {
        $coupon = ShopCoupon::query()
            ->where('tenant_id', $this->tenantId)
            ->whereKey($couponId)
            ->first();

        abort_if(! $coupon, 404);

        return $coupon;
    }

    private function resetForm(): void
    {
        $this->reset([
            'editingCouponId',
            'isEditing',
            'code',
            'amount',
            'startedAt',
            'endedAt',
            'totalQuantity',
            'availableQuantity',
            'userDailyLimit',
            'minimumOrderAmount',
            'remark',
            'userId',
        ]);

        $this->type = 'fixed';
        $this->status = 'active';
        $this->minimumOrderAmount = '0';
        $this->resetValidation();
    }

    private function formatForInput(mixed $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        if ($value instanceof CarbonInterface) {
            return $value->format('Y-m-d\TH:i');
        }

        return $value;
    }

    private function normalizeDateTime(?string $value): ?string
    {
        return blank($value)
            ? null
            : str_replace('T', ' ', $value).(strlen($value) === 16 ? ':00' : '');
    }

    private function blankToNull(mixed $value): mixed
    {
        return blank($value) ? null : $value;
    }

    private function handleAmountTriggerException(QueryException $exception): bool
    {
        $sqlState = $exception->errorInfo[0] ?? null;
        $errno = $exception->errorInfo[1] ?? null;
        $message = $exception->getMessage();

        if ($sqlState !== '45000' && (int) $errno !== 1644) {
            return false;
        }

        if (str_contains($message, 'Percentage coupon amount')) {
            $this->addError('amount', 'Percentage coupon amount must be between 0 and 100.');
            return true;
        }

        if (str_contains($message, 'Coupon amount cannot be less than zero')) {
            $this->addError('amount', 'Coupon amount cannot be less than zero.');
            return true;
        }

        $this->addError('amount', 'Coupon amount is invalid.');
        return true;
    }
}

<?php

namespace App\Livewire\Admin;

use App\Models\ShopShippingRule;
use App\Support\AdminTenantScope;
use Carbon\CarbonInterface;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class ShippingRulesPage extends Component
{
    use WithPagination;

    private const OVERLAP_TRIGGER_MESSAGE = 'Active time range overlaps an existing active record for this tenant';

    public string $tenantId;

    public string $statusFilter = '';

    public ?int $editingRuleId = null;

    public bool $isEditing = false;

    public string $status = 'active';

    public string $startedAt = '';

    public string $endedAt = '';

    public string $minimumAmount = '0';

    public string $shippingFeeType = 'cod';

    public string $shippingDaysText = '';

    public ?string $remark = null;

    public function mount(AdminTenantScope $tenantScope): void
    {
        $admin = Auth::guard('admin')->user();
        $this->tenantId = $admin->isShop()
            ? $admin->tenant_id
            : (string) $tenantScope->currentTenantId();

        abort_if(blank($this->tenantId), 404);
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        $this->resetForm();
        $this->isEditing = true;
    }

    public function edit(int $ruleId): void
    {
        $rule = $this->findScopedRule($ruleId);

        $this->editingRuleId = $rule->id;
        $this->isEditing = true;
        $this->status = $rule->status;
        $this->startedAt = $this->formatForInput($rule->started_at) ?? '';
        $this->endedAt = $this->formatForInput($rule->ended_at) ?? '';
        $this->minimumAmount = (string) $rule->minimum_amount;
        $this->shippingFeeType = $rule->shipping_fee_type;
        $this->shippingDaysText = $rule->shipping_days_text;
        $this->remark = $rule->remark;
        $this->resetValidation();
    }

    public function cancelEdit(): void
    {
        $this->resetForm();
    }

    public function save(): void
    {
        $validated = $this->validate($this->shippingRuleRules());

        $payload = [
            'tenant_id' => $this->tenantId,
            'status' => $validated['status'],
            'started_at' => $this->normalizeDateTime($validated['startedAt']),
            'ended_at' => $this->normalizeDateTime($validated['endedAt']),
            'minimum_amount' => $validated['minimumAmount'],
            'shipping_fee_type' => $validated['shippingFeeType'],
            'shipping_days_text' => $validated['shippingDaysText'],
            'remark' => $this->blankToNull($validated['remark']),
        ];

        try {
            if ($this->editingRuleId) {
                $this->findScopedRule($this->editingRuleId)->update($payload);
            } else {
                ShopShippingRule::query()->create($payload);
            }
        } catch (QueryException $exception) {
            if ($this->handleOverlapTriggerException($exception)) {
                return;
            }

            throw $exception;
        }

        $this->resetForm();
    }

    public function delete(int $ruleId): void
    {
        $this->findScopedRule($ruleId)->delete();

        if ($this->editingRuleId === $ruleId) {
            $this->resetForm();
        }
    }

    public function render()
    {
        return view('admin.shipping-rules-page', [
            'rules' => $this->shippingRulesQuery()->paginate(10),
        ])->layout('admin.layout')
            ->title('Shipping rules');
    }

    private function shippingRulesQuery()
    {
        return ShopShippingRule::query()
            ->where('tenant_id', $this->tenantId)
            ->when($this->statusFilter !== '', fn ($query) => $query->where('status', $this->statusFilter))
            ->orderByDesc('started_at')
            ->orderByDesc('id');
    }

    private function shippingRuleRules(): array
    {
        return [
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'startedAt' => ['required', 'date'],
            'endedAt' => ['required', 'date', 'after:startedAt'],
            'minimumAmount' => ['required', 'numeric', 'min:0'],
            'shippingFeeType' => ['required', Rule::in(['cod', 'free', 'prepaid'])],
            'shippingDaysText' => ['required', 'string', 'max:255'],
            'remark' => ['nullable', 'string'],
        ];
    }

    private function findScopedRule(int $ruleId): ShopShippingRule
    {
        $rule = ShopShippingRule::query()
            ->where('tenant_id', $this->tenantId)
            ->whereKey($ruleId)
            ->first();

        abort_if(! $rule, 404);

        return $rule;
    }

    private function resetForm(): void
    {
        $this->reset([
            'editingRuleId',
            'isEditing',
            'startedAt',
            'endedAt',
            'minimumAmount',
            'shippingDaysText',
            'remark',
        ]);

        $this->status = 'active';
        $this->minimumAmount = '0';
        $this->shippingFeeType = 'cod';
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

    private function normalizeDateTime(string $value): string
    {
        return str_replace('T', ' ', $value).(strlen($value) === 16 ? ':00' : '');
    }

    private function blankToNull(mixed $value): mixed
    {
        return blank($value) ? null : $value;
    }

    private function handleOverlapTriggerException(QueryException $exception): bool
    {
        $sqlState = $exception->errorInfo[0] ?? null;
        $errno = $exception->errorInfo[1] ?? null;
        $message = $exception->getMessage();

        if ($sqlState !== '45000' && (int) $errno !== 1644) {
            return false;
        }

        if (str_contains($message, self::OVERLAP_TRIGGER_MESSAGE)) {
            $this->addError('startedAt', self::OVERLAP_TRIGGER_MESSAGE);
            return true;
        }

        $this->addError('startedAt', 'Shipping rule date range overlaps another active rule.');
        return true;
    }
}

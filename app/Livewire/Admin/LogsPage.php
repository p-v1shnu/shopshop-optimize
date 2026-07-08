<?php

namespace App\Livewire\Admin;

use App\Models\OtpLog;
use App\Models\ShippingLog;
use App\Models\WebhookLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class LogsPage extends Component
{
    use WithPagination;

    public string $logType = 'webhook';

    public string $search = '';

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    public function updatedLogType(): void
    {
        validator(['logType' => $this->logType], [
            'logType' => ['required', Rule::in(['webhook', 'shipping', 'otp'])],
        ])->validate();

        $this->resetPage();
    }

    public function updatedSearch(): void
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

    public function render()
    {
        return view('admin.logs-page', [
            'logs' => $this->logsQuery()->paginate(10),
        ])->layout('admin.layout')
            ->title('Logs');
    }

    public function formatJson(mixed $value): string
    {
        return json_encode($value ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '{}';
    }

    public function maskedOtpData(OtpLog $log): array
    {
        return $this->maskOtpValue($log->data ?? [], (string) $log->otp);
    }

    private function logsQuery(): Builder
    {
        return match ($this->logType) {
            'shipping' => $this->shippingLogsQuery(),
            'otp' => $this->otpLogsQuery(),
            default => $this->webhookLogsQuery(),
        };
    }

    private function webhookLogsQuery(): Builder
    {
        return $this->applyDateRange(
            WebhookLog::query()
                ->when($this->search !== '', function (Builder $query): void {
                    $search = '%'.$this->search.'%';

                    $query->where(function (Builder $query) use ($search): void {
                        $query->where('type', 'like', $search)
                            ->orWhere('message', 'like', $search)
                            ->orWhere('model', 'like', $search);
                    });
                })
        )->orderByDesc('created_at')
            ->orderByDesc('id');
    }

    private function shippingLogsQuery(): Builder
    {
        return $this->applyDateRange(
            ShippingLog::query()
                ->when($this->search !== '', function (Builder $query): void {
                    $search = '%'.$this->search.'%';

                    $query->where(function (Builder $query) use ($search): void {
                        $query->where('provider', 'like', $search)
                            ->orWhere('type', 'like', $search)
                            ->orWhere('provider_reference', 'like', $search);
                    });
                })
        )->orderByDesc('created_at')
            ->orderByDesc('id');
    }

    private function otpLogsQuery(): Builder
    {
        return $this->applyDateRange(
            OtpLog::query()
                ->when($this->search !== '', function (Builder $query): void {
                    $search = '%'.$this->search.'%';

                    $query->where(function (Builder $query) use ($search): void {
                        $query->where('provider', 'like', $search)
                            ->orWhere('msisdn', 'like', $search)
                            ->orWhere('provider_reference', 'like', $search);
                    });
                })
        )->orderByDesc('created_at')
            ->orderByDesc('id');
    }

    private function applyDateRange(Builder $query): Builder
    {
        return $query
            ->when($this->dateFrom, fn (Builder $query) => $query->where('created_at', '>=', $this->dateFrom.' 00:00:00'))
            ->when($this->dateTo, fn (Builder $query) => $query->where('created_at', '<=', $this->dateTo.' 23:59:59'));
    }

    private function maskOtpValue(mixed $value, string $otp): mixed
    {
        if (is_array($value)) {
            return collect($value)
                ->mapWithKeys(function (mixed $item, string|int $key) use ($otp): array {
                    if (is_string($key) && Str::lower($key) === 'otp') {
                        return [$key => '***'];
                    }

                    return [$key => $this->maskOtpValue($item, $otp)];
                })
                ->all();
        }

        if (is_string($value) && $otp !== '') {
            // Substring replace, not exact match: provider payloads commonly embed
            // the OTP inside a full SMS sentence (e.g. "ທ່ານໄດ້ຮັບລະຫັດ: 123456 ຈາກ ...",
            // see OtpLoginModal::sendOtp()), so an exact-equality check would miss it.
            if (str_contains($value, $otp)) {
                return str_replace($otp, '***', $value);
            }
        }

        return $value;
    }
}

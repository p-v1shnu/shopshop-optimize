<?php

namespace App\Support;

use App\Models\AdminActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Throwable;

class AdminActivityLogger
{
    public function __construct(private readonly AdminTenantScope $tenantScope)
    {
    }

    public function log(string $action, ?Model $subject = null, array $detail = []): void
    {
        try {
            $admin = Auth::guard('admin')->user();

            $this->write([
                'admin_id' => $admin?->id,
                'tenant_id' => $this->tenantId($admin),
                'action' => $action,
                'subject_type' => $subject?->getMorphClass(),
                'subject_id' => $subject ? (string) $subject->getKey() : null,
                'detail' => $detail === [] ? null : $detail,
                'created_at' => now(),
            ]);
        } catch (Throwable $exception) {
            Log::error('Failed to write admin activity log.', [
                'action' => $action,
                'exception' => $exception,
            ]);
        }
    }

    private function tenantId(mixed $admin): ?string
    {
        if ($admin?->isShop()) {
            return $admin->tenant_id;
        }

        return $this->tenantScope->currentTenantId();
    }

    protected function write(array $payload): void
    {
        AdminActivityLog::query()->create($payload);
    }
}

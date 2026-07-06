<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Session;

class AdminTenantScope
{
    public const SESSION_KEY = 'admin_current_tenant_id';

    public function currentTenantId(): ?string
    {
        return Session::get(self::SESSION_KEY);
    }

    public function apply(Builder $query, ?string $tenantId = null): Builder
    {
        return $query->where('tenant_id', $tenantId ?? $this->currentTenantId());
    }
}

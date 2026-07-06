<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Support\AdminTenantScope;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class SetAdminCurrentShop
{
    public function handle(Request $request, Closure $next): Response
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return $next($request);
        }

        if ($admin->isShop()) {
            if (empty($admin->tenant_id)) {
                return response('', 403);
            }

            $request->session()->put(AdminTenantScope::SESSION_KEY, $admin->tenant_id);
        } else {
            $currentTenantId = $request->session()->get(AdminTenantScope::SESSION_KEY);
            $tenantExists = $currentTenantId
                ? Tenant::query()->whereKey($currentTenantId)->exists()
                : false;

            if (!$tenantExists) {
                $firstTenantId = Tenant::query()
                    ->where('status', 'active')
                    ->orderBy('name')
                    ->value('id');

                if ($firstTenantId) {
                    $request->session()->put(AdminTenantScope::SESSION_KEY, $firstTenantId);
                }
            }
        }

        $currentTenantId = $request->session()->get(AdminTenantScope::SESSION_KEY);

        View::share([
            'adminTenants' => $admin->isSuper()
                ? Tenant::query()->orderBy('name')->get(['id', 'name'])
                : collect(),
            'currentAdminTenant' => $currentTenantId
                ? Tenant::query()->find($currentTenantId)
                : null,
        ]);

        return $next($request);
    }
}

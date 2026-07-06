<?php

use App\Models\Tenant;

foreach (config('tenancy.central_domains') as $domain) {
    Route::domain($domain)->group(function () {

        Route::view('/', 'frontend.central-domain');

        // ================================================================================

        // Invoice webhook redirect to tenant domain
        // https://shopshop.la/[tenant_id]/shop/orders/[order_id] => https://[tenant_id].shopshop.la/shop/orders/[order_id]
        Route::get('/{tenant_id}/shop/orders/{order_id}', function ($tenant_id, $order_id) {
            $tenant = Tenant::query()
                ->with('domains:tenant_id,domain') // Eager load domains with specific columns
                ->where('id', $tenant_id)
                ->firstOrFail();

            $tenantDomain = $tenant->domains->first();

            if ($tenantDomain) {
                $url = "https://$tenantDomain->domain/shop/orders/$order_id";
                return redirect($url);
            }

            abort(404);
        });

        // ================================================================================

        // Error routes for middleware-enabled error pages
        Route::get('/error/{code}', function ($code) {
            $viewName = "errors.{$code}";

            // Check if specific error view exists, fallback to generic error view
            if (view()->exists($viewName)) {
                return response()->view($viewName, [], $code);
            }

            return response()->view('errors.500', [], $code);
        })->where('code', '[0-9]+');

    });
}

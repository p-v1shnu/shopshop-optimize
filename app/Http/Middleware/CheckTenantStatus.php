<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckTenantStatus
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = tenant();

        if ($tenant && $tenant->status === 'inactive') {
            $mainDomain = config('custom.main_domain');
            $protocol = $request->secure() ? 'https' : 'http';

            return redirect()->away("{$protocol}://{$mainDomain}");
        }

        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CheckProfile
{
    public function handle(Request $request, Closure $next): Response | RedirectResponse
    {
        // Guest can view any unrestricted routes
        if (!auth()->check()) {
            return $next($request);
        }

        // If the request is for the profile edit route, allow access
        // This code must be placed before the check for complete profile
        if ($request->route()->getName() === 'profile.edit') {
            return $next($request);
        }

        // If user is logged-in, they must have a complete profile to access other routes
        if (auth()->check() && !auth()->user()->had_complete_profile) {
            return redirect()->route('profile.edit');
        }

        return $next($request);
    }
}

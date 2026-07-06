<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AddRequestContext
{
    public function handle(Request $request, Closure $next): Response
    {
        Context::add('request_id', Str::uuid()->toString());

        if ($request->user()) {
            Context::add('user_id', $request->user()->id);
        }

        return $next($request);
    }
}

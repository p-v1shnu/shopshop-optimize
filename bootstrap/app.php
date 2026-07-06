<?php

use App\Http\Middleware\AddRequestContext;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Spatie\LaravelFlare\Facades\Flare;
use Stancl\Tenancy\Exceptions\TenantCouldNotBeIdentifiedOnDomainException;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up'
    )
    ->withMiddleware(function (Middleware $middleware) {

        $middleware->group('universal', []);

        $middleware->redirectTo(
            guests: '/login',
            users: '/',
        );

        $middleware->prepend(AddRequestContext::class);

    })
    ->withExceptions(function (Exceptions $exceptions) {

        $exceptions->render(function (TenantCouldNotBeIdentifiedOnDomainException $e, $request) {
            return redirect()->away(config('app.url'));
        });

        $exceptions->render(function (HttpException $e, $request) {
            $statusCode = $e->getStatusCode();

            // Handle API requests with JSON responses
            if ($request->is('api/*')) {
                $messages = [
                    404 => 'Not found',
                    401 => 'Unauthorized',
                    403 => 'Forbidden',
                    429 => 'Too many requests',
                    500 => 'Internal server error',
                    503 => 'Service unavailable',
                ];

                return response()->json([
                    'message' => $messages[$statusCode] ?? 'An error occurred'
                ], $statusCode);
            }

            // Handle web requests - try sub-request first, then fallback to direct view
            try {
                $subRequest = Request::create(
                    "/error/{$statusCode}",
                    'GET',
                    [],
                    $request->cookies->all(),
                    [],
                    $request->server->all()
                );

                $subRequest->headers->replace($request->headers->all());

                $kernel = app(Kernel::class);
                return $kernel->handle($subRequest);

            } catch (Exception $subRequestException) {
                // Fallback to direct view rendering
                $viewName = "errors.{$statusCode}";

                if (view()->exists($viewName)) {
                    return response()->view($viewName, [], $statusCode);
                }

                return response()->view('errors.500', [], $statusCode);
            }
        });

        Flare::handles($exceptions);

    })
    ->create();

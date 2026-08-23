<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);

        // Render (and most PaaS hosts) terminate TLS at an edge proxy and forward plain HTTP
        // to the container, signalling the original scheme via X-Forwarded-Proto. Without this,
        // Laravel thinks every request is HTTP and generates http:// asset/URL links even when
        // the page was loaded over https:// — the browser then blocks them as mixed content.
        $middleware->trustProxies(at: '*');

        // DGateway posts here with no Laravel session/CSRF token — authenticity is verified by
        // HMAC signature inside DGatewayWebhookController instead.
        $middleware->validateCsrfTokens(except: ['webhooks/dgateway']);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();

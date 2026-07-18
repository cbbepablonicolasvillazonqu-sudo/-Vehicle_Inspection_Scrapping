<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Confía en los encabezados X-Forwarded-* del proxy que tenga delante
        // (túnel de Cloudflare en demos, balanceador de Hostinger en prod).
        // Sin esto, detrás de HTTPS Laravel generaría URLs http:// (contenido mixto).
        $middleware->trustProxies(at: '*');

        // Fija el idioma (es/en) en cada petición web según la preferencia
        // del usuario o de la sesión.
        $middleware->web(append: [
            \App\Http\Middleware\EstablecerIdioma::class,
        ]);

        // Middleware de roles y permisos (Spatie Laravel-Permission).
        // Protegen rutas y componentes Livewire de página completa.
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();

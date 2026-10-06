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
        // (túnel de Cloudflare en demos, CDN de Hostinger en prod).
        // Sin esto, detrás de HTTPS Laravel generaría URLs http:// (contenido mixto).
        //
        // No es una lista de IP a propósito: Hostinger no publica las de su
        // CDN, y una lista equivocada deja la app sin detectar el HTTPS (y sin
        // HSTS). Lo que esto permite falsear es la IP del cliente, que solo se
        // usa en el límite de intentos del login, y ese límite tiene además un
        // tope por correo que no depende de la IP (LoginRequest).
        $middleware->trustProxies(at: '*');

        // Como se confía en todos los proxies, hay que acotar qué host se
        // acepta: sin esto cualquiera puede falsear la cabecera Host y
        // envenenar los enlaces que la app genera. Laravel desactiva esta
        // comprobación sola en el entorno local y durante las pruebas.
        $middleware->trustHosts(at: fn () => config('forte.hosts_confiables'));

        // Fija el idioma (es/en) en cada petición web según la preferencia
        // del usuario o de la sesión, y agrega las cabeceras de seguridad.
        $middleware->web(append: [
            \App\Http\Middleware\EstablecerIdioma::class,
            \App\Http\Middleware\CabecerasSeguridad::class,
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

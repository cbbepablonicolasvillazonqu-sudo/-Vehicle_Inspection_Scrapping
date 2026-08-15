<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cabeceras de seguridad en cada respuesta web.
 *
 * Van acá y no en .htaccess para que viajen con la app: sobreviven a un cambio
 * de servidor y se pueden probar desde la suite.
 */
class CabecerasSeguridad
{
    public function handle(Request $request, Closure $next): Response
    {
        $respuesta = $next($request);

        // Evita que el navegador adivine el tipo de un archivo servido desde
        // /storage (fotos y contratos subidos por los usuarios).
        $respuesta->headers->set('X-Content-Type-Options', 'nosniff');

        // La app no se embebe en ningún lado: nadie debería poder ponerla en
        // un iframe para engañar a un usuario logueado.
        $respuesta->headers->set('X-Frame-Options', 'SAMEORIGIN');

        // No filtrar la URL completa (que puede incluir el id de un vehículo)
        // al salir hacia Google Maps desde la ficha de recojo.
        $respuesta->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // HSTS solo cuando el HTTPS ya está estable: el navegador recuerda esta
        // instrucción durante un año y revertirla es lento.
        if (config('forte.forzar_https') && $request->secure()) {
            $respuesta->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $respuesta;
    }
}

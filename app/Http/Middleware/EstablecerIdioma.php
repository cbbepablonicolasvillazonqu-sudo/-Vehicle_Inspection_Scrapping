<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Fija el idioma de la petición según esta prioridad:
 *   1. Preferencia guardada del usuario autenticado (users.locale)
 *   2. Idioma elegido en la sesión (para invitados en el login)
 *   3. Idioma por defecto de la app (config app.locale = es)
 */
class EstablecerIdioma
{
    /** Idiomas soportados por la aplicación. */
    public const SOPORTADOS = ['es', 'en'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->user()?->locale
            ?? $request->session()->get('locale')
            ?? config('app.locale');

        if (! in_array($locale, self::SOPORTADOS, true)) {
            $locale = config('app.locale');
        }

        app()->setLocale($locale);
        // Sincroniza los nombres de meses/días (ej. "julio 2026" / "July 2026").
        Carbon::setLocale($locale);

        return $next($request);
    }
}

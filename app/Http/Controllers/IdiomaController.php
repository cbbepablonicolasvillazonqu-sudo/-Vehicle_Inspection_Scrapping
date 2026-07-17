<?php

namespace App\Http\Controllers;

use App\Http\Middleware\EstablecerIdioma;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class IdiomaController extends Controller
{
    /**
     * Cambia el idioma. Lo guarda en la sesión y, si hay usuario, también
     * en su perfil para que persista entre dispositivos.
     */
    public function cambiar(Request $request, string $locale): RedirectResponse
    {
        if (! in_array($locale, EstablecerIdioma::SOPORTADOS, true)) {
            $locale = config('app.locale');
        }

        $request->session()->put('locale', $locale);

        if ($usuario = $request->user()) {
            $usuario->forceFill(['locale' => $locale])->save();
        }

        return back();
    }
}

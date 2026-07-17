<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Forte Towing') }}</title>

        <!-- PWA: instalable en la pantalla de inicio del celular -->
        <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
        <meta name="theme-color" content="#0f172a">
        <link rel="icon" type="image/png" href="{{ asset('iconos/icono-192.png') }}">
        <link rel="apple-touch-icon" href="{{ asset('iconos/apple-touch-icon.png') }}">
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
        <meta name="apple-mobile-web-app-title" content="Forte Towing">

        <!-- Scripts y estilos compilados -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-slate-800 antialiased">
        {{-- Fondo oscuro con degradado y un halo azul para dar profundidad --}}
        <div class="relative min-h-screen flex flex-col justify-center items-center px-4 py-10 overflow-hidden
                    bg-gradient-to-b from-slate-900 via-slate-900 to-blue-950">
            <div class="pointer-events-none absolute -top-32 left-1/2 -translate-x-1/2 w-[36rem] h-[36rem] rounded-full bg-blue-600/20 blur-3xl"></div>

            <div class="relative w-full sm:max-w-md">
                <div class="flex flex-col items-center mb-6">
                    <span class="grid place-items-center w-16 h-16 rounded-2xl bg-blue-700 text-white shadow-lg shadow-blue-900/50">
                        <x-application-logo class="w-9 h-9" />
                    </span>
                    <h1 class="mt-4 text-2xl font-extrabold text-white tracking-tight">Forte Towing</h1>
                    <p class="text-sm text-slate-400">{{ __('Inventario de vehículos') }}</p>
                </div>

                <div class="bg-white px-6 py-7 shadow-2xl rounded-2xl">
                    {{ $slot }}
                </div>

                <div class="mt-6 flex justify-center">
                    <x-selector-idioma class="!bg-white/10 !text-slate-300" />
                </div>
            </div>
        </div>

        <!-- Registro del service worker (PWA) -->
        <script>
            if ('serviceWorker' in navigator) {
                window.addEventListener('load', function () {
                    navigator.serviceWorker.register('/sw.js');
                });
            }
        </script>
    </body>
</html>

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Forte Towing') }}</title>

        <!-- PWA: instalable en la pantalla de inicio del celular -->
        <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
        <meta name="theme-color" content="#1e40af">
        <link rel="icon" type="image/png" href="{{ asset('iconos/icono-192.png') }}">
        <link rel="apple-touch-icon" href="{{ asset('iconos/apple-touch-icon.png') }}">
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="default">
        <meta name="apple-mobile-web-app-title" content="Forte Towing">

        <!-- Scripts y estilos compilados -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-10 sm:pt-0 bg-gray-100 px-4">
            <div class="flex flex-col items-center">
                <a href="/" class="flex items-center gap-3">
                    <x-application-logo class="w-14 h-14 fill-current text-blue-800" />
                </a>
                <h1 class="mt-3 text-2xl font-bold text-gray-800">Forte Towing</h1>
                <p class="text-sm text-gray-500">Inventario de vehículos</p>
            </div>

            <div class="w-full sm:max-w-md mt-6 px-6 py-6 bg-white shadow-md overflow-hidden rounded-xl">
                {{ $slot }}
            </div>

            <div class="mt-6">
                <x-selector-idioma />
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

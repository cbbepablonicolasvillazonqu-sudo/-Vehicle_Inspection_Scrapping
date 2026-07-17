<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Forte Towing') }}</title>

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
        </div>
    </body>
</html>

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ isset($title) ? $title.' · ' : '' }}{{ config('app.name', 'Forte Towing') }}</title>

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
        @livewireStyles
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100 pb-10">
            @include('layouts.navigation')

            <!-- Encabezado de página -->
            @isset($header)
                <header class="bg-white shadow">
                    <div class="max-w-7xl mx-auto py-4 px-4 sm:py-6 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Mensajes flash (tras redirecciones) -->
            @if (session('ok'))
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4"
                     x-data="{ visible: true }" x-show="visible" x-transition.opacity>
                    <div class="rounded-lg bg-green-100 border border-green-300 text-green-800 px-4 py-3 flex items-center justify-between">
                        <span class="font-medium">{{ session('ok') }}</span>
                        <button type="button" class="text-green-700 font-bold px-2" @click="visible = false">✕</button>
                    </div>
                </div>
            @endif

            <!-- Contenido -->
            <main>
                {{ $slot }}
            </main>
        </div>

        <!-- Notificaciones flotantes disparadas por Livewire ($this->dispatch('notificar', mensaje: '...')) -->
        <div x-data="{ mostrar: false, mensaje: '' }"
             x-on:notificar.window="mensaje = $event.detail.mensaje ?? 'Listo'; mostrar = true; clearTimeout(window._toastTimer); window._toastTimer = setTimeout(() => mostrar = false, 2600)"
             x-show="mostrar" x-transition
             class="fixed bottom-4 inset-x-0 flex justify-center z-50 px-4" style="display: none;">
            <div class="bg-gray-900 text-white text-base font-medium px-5 py-3 rounded-xl shadow-lg" x-text="mensaje"></div>
        </div>

        @livewireScripts

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

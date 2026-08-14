<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ isset($title) ? $title.' · ' : '' }}{{ config('app.name', 'Forte Towing') }}</title>

        <!-- PWA: instalable en la pantalla de inicio del celular -->
        <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
        <meta name="theme-color" content="#1e3a8a">
        <link rel="icon" type="image/png" href="{{ asset('iconos/icono-192.png') }}">
        <link rel="apple-touch-icon" href="{{ asset('iconos/apple-touch-icon.png') }}">
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
        <meta name="apple-mobile-web-app-title" content="Forte Towing">

        <!-- Scripts y estilos compilados -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="font-sans antialiased">
        {{-- pb-24 en móvil deja aire para la barra de navegación inferior fija --}}
        <div class="min-h-screen bg-gradient-to-b from-slate-100 to-slate-200/60 pb-24 lg:pb-10">
            @include('layouts.navigation')

            <!-- Encabezado de página -->
            @isset($header)
                <header class="bg-white/80 backdrop-blur border-b border-slate-200">
                    <div class="max-w-7xl mx-auto py-4 px-4 sm:py-6 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Mensajes flash (tras redirecciones) -->
            @if (session('ok'))
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4"
                     x-data="{ visible: true }" x-show="visible" x-transition.opacity
                     x-init="setTimeout(() => visible = false, 5000)">
                    <div class="rounded-xl bg-green-50 border border-green-200 text-green-800 px-4 py-3 flex items-center gap-3 shadow-sm">
                        <x-icono nombre="check" clase="w-5 h-5 shrink-0 text-green-600" />
                        <span class="font-medium flex-1">{{ session('ok') }}</span>
                        <button type="button" class="text-green-600 hover:text-green-800 font-bold px-1" @click="visible = false" aria-label="Cerrar">✕</button>
                    </div>
                </div>
            @endif

            <!-- Contenido -->
            <main>
                {{ $slot }}
            </main>
        </div>

        <!-- Notificaciones flotantes: las dispara Livewire ($this->dispatch('notificar', mensaje: '...'))
             y también el JS del front cuando falla la red (con tipo: 'error'). -->
        <div x-data="{ mostrar: false, mensaje: '', tipo: 'ok' }"
             x-on:notificar.window="
                mensaje = $event.detail.mensaje ?? '{{ __('Listo') }}';
                tipo = $event.detail.tipo ?? 'ok';
                mostrar = true;
                clearTimeout(window._toastTimer);
                window._toastTimer = setTimeout(() => mostrar = false, tipo === 'error' ? 4500 : 2600)"
             x-show="mostrar"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 translate-y-4"
             class="fixed bottom-24 sm:bottom-6 inset-x-0 flex justify-center z-[60] px-4 pointer-events-none" style="display: none;">
            <div class="text-white text-base font-medium px-5 py-3 rounded-2xl shadow-2xl flex items-center gap-2.5"
                 :class="tipo === 'error' ? 'bg-red-600' : 'bg-slate-900'">
                <span x-show="tipo === 'error'" class="text-lg leading-none" aria-hidden="true">⚠</span>
                <span x-show="tipo !== 'error'"><x-icono nombre="check" clase="w-5 h-5 text-green-400" /></span>
                <span x-text="mensaje"></span>
            </div>
        </div>

        {{-- Textos que usa el JS al fallar la red. Van acá y no en app.js para que
             pasen por __() y los detecte el verificador de traducciones. --}}
        <script>
            window.avisos = {
                sinConexion: @js(__('No se pudo guardar. Revisá tu conexión e intentá de nuevo.')),
                subidaFallida: @js(__('No se pudo subir el archivo. Revisá tu conexión.')),
            };
        </script>

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

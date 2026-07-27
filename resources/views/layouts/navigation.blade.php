@php
    // Enlaces de navegación según permisos (se usan en la barra superior y la inferior).
    $enlaces = collect([
        ['ruta' => 'dashboard', 'patron' => 'dashboard', 'icono' => 'panel', 'texto' => __('Panel'), 'ver' => true],
        ['ruta' => 'vehiculos.index', 'patron' => 'vehiculos.*', 'icono' => 'vehiculo', 'texto' => __('Vehículos'), 'ver' => auth()->user()->can('ver inventario')],
        ['ruta' => 'recojos.asignar', 'patron' => 'recojos.*', 'icono' => 'etiqueta', 'texto' => __('Recojos'), 'ver' => auth()->user()->can('asignar recojo')],
        ['ruta' => 'junk.masivo', 'patron' => 'junk.*', 'icono' => 'engranaje', 'texto' => __('Junk car'), 'ver' => auth()->user()->can('enviar a junk')],
        ['ruta' => 'reportes.ganancias', 'patron' => 'reportes.*', 'icono' => 'reportes', 'texto' => __('Reportes'), 'ver' => auth()->user()->can('ver ganancias')],
        ['ruta' => 'usuarios.index', 'patron' => 'usuarios.*', 'icono' => 'usuarios', 'texto' => __('Usuarios'), 'ver' => auth()->user()->can('gestionar usuarios')],
    ])->where('ver', true);
@endphp

<nav x-data="{ open: false }" class="sticky top-0 z-40 bg-white/90 backdrop-blur border-b border-slate-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5">
                        <span class="grid place-items-center w-10 h-10 rounded-xl bg-blue-700 text-white shadow-sm shadow-blue-700/30">
                            <x-application-logo class="w-6 h-6" />
                        </span>
                        <span class="hidden sm:block font-extrabold text-slate-800 tracking-tight leading-none">
                            Forte<span class="text-blue-700">Towing</span>
                        </span>
                    </a>
                </div>

                <!-- Enlaces (escritorio) -->
                <div class="hidden sm:flex sm:-my-px sm:ms-8 gap-6">
                    @foreach ($enlaces as $e)
                        <x-nav-link :href="route($e['ruta'])" :active="request()->routeIs($e['patron'])">
                            <x-icono :nombre="$e['icono']" clase="w-5 h-5" />
                            {{ $e['texto'] }}
                        </x-nav-link>
                    @endforeach
                </div>
            </div>

            <!-- Idioma + usuario (escritorio) -->
            <div class="hidden sm:flex sm:items-center gap-3">
                <x-selector-idioma />

                <x-dropdown align="right" width="52">
                    <x-slot name="trigger">
                        <button class="flex items-center gap-2 rounded-full pl-1 pr-2.5 py-1 hover:bg-slate-100 transition">
                            <span class="grid place-items-center w-8 h-8 rounded-full bg-gradient-to-br from-blue-600 to-blue-800 text-white text-sm font-bold">
                                {{ mb_substr(Auth::user()->name, 0, 1) }}
                            </span>
                            <span class="text-left leading-tight">
                                <span class="block text-sm font-semibold text-slate-800">{{ Auth::user()->name }}</span>
                                <span class="block text-xs text-slate-400">{{ Auth::user()->nombreRol() }}</span>
                            </span>
                            <svg class="fill-current h-4 w-4 text-slate-400" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <div class="px-4 py-3 border-b border-slate-100">
                            <div class="text-sm font-semibold text-slate-800">{{ Auth::user()->name }}</div>
                            <div class="text-xs text-slate-400 truncate">{{ Auth::user()->email }}</div>
                        </div>
                        <x-dropdown-link :href="route('profile.edit')">
                            <x-icono nombre="usuario" clase="w-4 h-4 text-slate-400" />
                            {{ __('Mi perfil') }}
                        </x-dropdown-link>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">
                                <x-icono nombre="salir" clase="w-4 h-4 text-slate-400" />
                                {{ __('Cerrar sesión') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Móvil: idioma + avatar con menú -->
            <div class="flex items-center gap-2 sm:hidden">
                <x-selector-idioma />
                <x-dropdown align="right" width="52">
                    <x-slot name="trigger">
                        <button class="grid place-items-center w-10 h-10 rounded-full bg-gradient-to-br from-blue-600 to-blue-800 text-white text-sm font-bold shadow-sm">
                            {{ mb_substr(Auth::user()->name, 0, 1) }}
                        </button>
                    </x-slot>
                    <x-slot name="content">
                        <div class="px-4 py-3 border-b border-slate-100">
                            <div class="text-sm font-semibold text-slate-800">{{ Auth::user()->name }}</div>
                            <div class="text-xs text-slate-400">{{ Auth::user()->nombreRol() }} · {{ Auth::user()->email }}</div>
                        </div>
                        <x-dropdown-link :href="route('profile.edit')">
                            <x-icono nombre="usuario" clase="w-4 h-4 text-slate-400" />
                            {{ __('Mi perfil') }}
                        </x-dropdown-link>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">
                                <x-icono nombre="salir" clase="w-4 h-4 text-slate-400" />
                                {{ __('Cerrar sesión') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>
        </div>
    </div>
</nav>

{{-- Barra de navegación inferior fija (solo móvil) — estilo app nativa --}}
<nav class="sm:hidden fixed bottom-0 inset-x-0 z-40 bg-white/95 backdrop-blur border-t border-slate-200 shadow-elevada"
     style="padding-bottom: env(safe-area-inset-bottom);">
    <div class="grid h-16" style="grid-template-columns: repeat({{ max($enlaces->count(), 1) }}, minmax(0, 1fr));">
        @foreach ($enlaces as $e)
            @php $activo = request()->routeIs($e['patron']); @endphp
            <a href="{{ route($e['ruta']) }}"
               @class([
                   'flex flex-col items-center justify-center gap-0.5 text-[11px] font-medium transition',
                   'text-blue-700' => $activo,
                   'text-slate-400' => ! $activo,
               ])>
                <span @class(['grid place-items-center w-10 h-7 rounded-full transition', 'bg-blue-100' => $activo])>
                    <x-icono :nombre="$e['icono']" clase="w-6 h-6" />
                </span>
                {{ $e['texto'] }}
            </a>
        @endforeach
    </div>
</nav>

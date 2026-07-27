<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

        <div class="flex items-center gap-3">
            <span class="grid place-items-center w-11 h-11 rounded-full bg-gradient-to-br from-blue-600 to-blue-800 text-white text-lg font-bold shrink-0">
                {{ mb_substr(auth()->user()->name, 0, 1) }}
            </span>
            <div>
                <h2 class="font-bold text-lg text-slate-800 leading-tight">{{ __('Hola') }}, {{ auth()->user()->name }} 👋</h2>
                <p class="text-sm text-slate-500">{{ auth()->user()->nombreRol() }} · Forte Towing</p>
            </div>
        </div>

        {{-- Tarjetas por estado --}}
        @php
            $estilos = [
                'slate' => ['ring' => 'hover:ring-slate-300', 'chip' => 'bg-slate-100 text-slate-600', 'num' => 'text-slate-900'],
                'yellow' => ['ring' => 'hover:ring-yellow-300', 'chip' => 'bg-yellow-100 text-yellow-700', 'num' => 'text-yellow-600'],
                'green' => ['ring' => 'hover:ring-green-300', 'chip' => 'bg-green-100 text-green-700', 'num' => 'text-green-600'],
                'sky' => ['ring' => 'hover:ring-sky-300', 'chip' => 'bg-sky-100 text-sky-700', 'num' => 'text-sky-600'],
                'blue' => ['ring' => 'hover:ring-blue-300', 'chip' => 'bg-blue-100 text-blue-700', 'num' => 'text-blue-700'],
                'gray' => ['ring' => 'hover:ring-gray-300', 'chip' => 'bg-gray-200 text-gray-600', 'num' => 'text-gray-600'],
            ];
        @endphp

        <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-3">
            @foreach ($tarjetas as $t)
                @php $s = $estilos[$t['color']]; @endphp
                <a href="{{ $t['estado'] ? route('vehiculos.index', ['estado' => $t['estado']]) : route('vehiculos.index') }}"
                   class="tarjeta-interactiva p-4 hover:ring-2 {{ $s['ring'] }}">
                    <div class="flex items-center justify-between">
                        <span class="grid place-items-center w-9 h-9 rounded-xl {{ $s['chip'] }}">
                            <x-icono :nombre="$t['icono']" clase="w-5 h-5" />
                        </span>
                        <span class="text-3xl font-extrabold tabular {{ $s['num'] }}">{{ $t['n'] }}</span>
                    </div>
                    <div class="text-sm text-slate-500 mt-2">{{ $t['txt'] }}</div>
                </a>
            @endforeach
        </div>

        {{-- Finanzas (solo Admin) --}}
        @if ($finanzas)
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div class="rounded-2xl p-5 bg-gradient-to-br from-slate-800 to-slate-900 text-white shadow-md">
                    <div class="flex items-center gap-2 text-slate-300 text-sm">
                        <x-icono nombre="archivo" clase="w-4 h-4" />
                        {{ __('Total invertido en inventario actual') }}
                    </div>
                    <div class="text-3xl font-extrabold mt-2 tabular">{{ dinero($finanzas['invertido']) }}</div>
                    <div class="text-xs text-slate-400 mt-1">{{ __('compra + gastos de vehículos activos') }}</div>
                </div>

                <div class="tarjeta p-5 border-l-4 {{ $finanzas['gananciaMes'] >= 0 ? 'border-l-green-500' : 'border-l-red-500' }}">
                    <div class="flex items-center gap-2 text-slate-500 text-sm">
                        <x-icono nombre="calendario" clase="w-4 h-4" />
                        {{ __('Ganancia de :mes', ['mes' => now()->translatedFormat('F Y')]) }}
                    </div>
                    <div class="text-3xl font-extrabold mt-2 tabular {{ $finanzas['gananciaMes'] >= 0 ? 'text-green-700' : 'text-red-700' }}">
                        {{ dinero($finanzas['gananciaMes']) }}
                    </div>
                    <a href="{{ route('reportes.ganancias') }}" class="text-xs text-blue-700 hover:underline mt-1 inline-flex items-center gap-1">
                        {{ __('Ver reporte →') }}
                    </a>
                </div>

                <div class="tarjeta p-5 border-l-4 {{ $finanzas['gananciaAcumulada'] >= 0 ? 'border-l-green-500' : 'border-l-red-500' }}">
                    <div class="flex items-center gap-2 text-slate-500 text-sm">
                        <x-icono nombre="reportes" clase="w-4 h-4" />
                        {{ __('Ganancia acumulada') }}
                    </div>
                    <div class="text-3xl font-extrabold mt-2 tabular {{ $finanzas['gananciaAcumulada'] >= 0 ? 'text-green-700' : 'text-red-700' }}">
                        {{ dinero($finanzas['gananciaAcumulada']) }}
                    </div>
                    <div class="text-xs text-slate-400 mt-1">{{ __('ventas + Junk car históricos') }}</div>
                </div>
            </div>
        @endif

        {{-- Lista de vehículos con búsqueda y filtro --}}
        <livewire:vehiculos.lista-vehiculos :embebido="true" />
    </div>
</div>

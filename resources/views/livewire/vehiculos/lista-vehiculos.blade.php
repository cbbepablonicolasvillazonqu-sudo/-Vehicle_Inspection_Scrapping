<div class="{{ $embebido ? '' : 'py-6 px-4 sm:px-6 lg:px-8' }}">
    <div class="max-w-7xl mx-auto space-y-4">

        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-bold text-lg text-slate-800">{{ __('Vehículos') }}</h2>

            <div class="flex flex-wrap items-center gap-2">
                @can('exportar datos')
                    <a href="{{ route('exportar.vehiculos', ['formato' => 'xlsx', 'buscar' => $busqueda, 'estado' => $filtroEstado]) }}"
                       class="btn-secundario btn-sm text-green-700 border-green-200 hover:bg-green-50">
                        <x-icono nombre="descargar" clase="w-4 h-4" /> Excel
                    </a>
                    <a href="{{ route('exportar.vehiculos', ['formato' => 'csv', 'buscar' => $busqueda, 'estado' => $filtroEstado]) }}"
                       class="btn-secundario btn-sm">
                        <x-icono nombre="descargar" clase="w-4 h-4" /> CSV
                    </a>
                @endcan

                @can('crear vehiculos')
                    <a href="{{ route('vehiculos.crear') }}" class="btn-primario btn-sm">
                        <x-icono nombre="mas" clase="w-5 h-5" /> {{ __('Nuevo vehículo') }}
                    </a>
                @endcan
            </div>
        </div>

        {{-- Búsqueda y filtro --}}
        <div class="tarjeta p-3 grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div class="sm:col-span-2 relative">
                <x-icono nombre="buscar" clase="w-5 h-5 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" />
                <input type="search"
                       wire:model.live.debounce.250ms="busqueda"
                       placeholder="{{ __('Buscar por marca, modelo o VIN…') }}"
                       class="campo pl-10">
            </div>
            <div>
                <select wire:model.live="filtroEstado" class="campo">
                    <option value="">{{ __('Todos los estados') }}</option>
                    @foreach ($estados as $estado)
                        <option value="{{ $estado->value }}">{{ $estado->etiqueta() }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- Tarjetas de vehículos --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @forelse ($vehiculos as $vehiculo)
                <a href="{{ route('vehiculos.ficha', $vehiculo) }}"
                   class="tarjeta-interactiva overflow-hidden flex"
                   wire:key="veh-{{ $vehiculo->id }}">

                    {{-- Miniatura o placeholder --}}
                    <div class="relative w-28 shrink-0 bg-slate-100">
                        @if ($vehiculo->fotoPortada)
                            <img src="{{ $vehiculo->fotoPortada->url() }}" alt="{{ $vehiculo->nombreCompleto() }}"
                                 loading="lazy" class="absolute inset-0 w-full h-full object-cover">
                        @else
                            <div class="absolute inset-0 grid place-items-center text-slate-300">
                                <x-icono nombre="sin-foto" clase="w-9 h-9" />
                            </div>
                        @endif
                        @if ($vehiculo->fotos_count > 1)
                            <span class="absolute bottom-1 right-1 bg-slate-900/70 text-white text-[10px] font-semibold px-1.5 py-0.5 rounded-md flex items-center gap-0.5">
                                <x-icono nombre="camara" clase="w-3 h-3" /> {{ $vehiculo->fotos_count }}
                            </span>
                        @endif
                    </div>

                    {{-- Datos --}}
                    <div class="flex-1 min-w-0 p-3.5">
                        <div class="flex items-start justify-between gap-2">
                            <div class="font-bold text-slate-900 leading-tight truncate">{{ $vehiculo->nombreCompleto() }}</div>
                            <span class="w-2.5 h-2.5 mt-1.5 rounded-full shrink-0 {{ $vehiculo->estado->colorPunto() }}"></span>
                        </div>
                        <div class="text-xs text-slate-400 font-mono mt-0.5 truncate">{{ $vehiculo->vin }}</div>

                        <span class="chip mt-2 {{ $vehiculo->estado->colorBadge() }}">{{ $vehiculo->estado->etiquetaCorta() }}</span>

                        <div class="mt-2 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-slate-500 tabular">
                            <span class="inline-flex items-center gap-1"><x-icono nombre="velocimetro" clase="w-3.5 h-3.5" />{{ $vehiculo->millas !== null ? number_format($vehiculo->millas) : '—' }}</span>
                            <span class="inline-flex items-center gap-1"><x-icono nombre="calendario" clase="w-3.5 h-3.5" />{{ $vehiculo->fecha_compra?->format('d/m/y') ?? '—' }}</span>
                        </div>

                        @can('ver precios compra')
                            <div class="mt-1.5 text-sm text-slate-600 tabular">
                                {{ __('Compra:') }} <span class="font-bold text-slate-800">{{ dinero($vehiculo->precio_compra) }}</span>
                            </div>
                        @endcan

                        @if ($vehiculo->precio_sugerido !== null && ! $vehiculo->estado->esFinal())
                            <div class="mt-0.5 text-sm text-slate-600 tabular">
                                {{ __('Sugerido:') }} <span class="font-bold text-blue-800">{{ dinero($vehiculo->precio_sugerido) }}</span>
                            </div>
                        @endif
                    </div>
                </a>
            @empty
                <div class="col-span-full tarjeta p-10 text-center">
                    <x-icono nombre="vehiculo" clase="w-12 h-12 mx-auto text-slate-300" />
                    <p class="mt-3 text-slate-500">{{ __('No hay vehículos que coincidan con la búsqueda.') }}</p>
                </div>
            @endforelse
        </div>

        <div>{{ $vehiculos->links() }}</div>
    </div>
</div>

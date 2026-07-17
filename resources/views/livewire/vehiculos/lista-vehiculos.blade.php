<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">

        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800">Vehículos</h2>

            @can('crear vehiculos')
                <a href="{{ route('vehiculos.crear') }}"
                   class="inline-flex items-center px-4 py-3 bg-blue-700 hover:bg-blue-800 text-white text-base font-semibold rounded-xl shadow">
                    + Nuevo vehículo
                </a>
            @endcan
        </div>

        {{-- Búsqueda y filtro --}}
        <div class="bg-white shadow rounded-xl p-3 sm:p-4 grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div class="sm:col-span-2">
                <input type="search"
                       wire:model.live.debounce.400ms="busqueda"
                       placeholder="Buscar por marca, modelo o VIN…"
                       class="w-full border-gray-300 rounded-xl text-base py-3 focus:border-blue-500 focus:ring-blue-500">
            </div>
            <div>
                <select wire:model.live="filtroEstado"
                        class="w-full border-gray-300 rounded-xl text-base py-3 focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Todos los estados</option>
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
                   class="block bg-white shadow rounded-xl p-4 hover:shadow-md hover:ring-2 hover:ring-blue-200 transition"
                   wire:key="veh-{{ $vehiculo->id }}">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <div class="font-bold text-gray-900 text-lg leading-tight truncate">
                                {{ $vehiculo->nombreCompleto() }}
                            </div>
                            <div class="text-xs text-gray-500 font-mono mt-1">{{ $vehiculo->vin }}</div>
                        </div>
                        <span class="w-3 h-3 mt-1.5 rounded-full shrink-0 {{ $vehiculo->estado->colorPunto() }}"></span>
                    </div>

                    <div class="mt-3 flex flex-wrap items-center gap-2 text-sm text-gray-600">
                        <span class="px-2.5 py-1 rounded-full text-xs font-semibold {{ $vehiculo->estado->colorBadge() }}">
                            {{ $vehiculo->estado->etiquetaCorta() }}
                        </span>
                        <span>{{ number_format($vehiculo->millas) }} mi</span>
                        <span>·</span>
                        <span>{{ $vehiculo->fecha_compra->format('d/m/Y') }}</span>
                        @if ($vehiculo->fotos_count)
                            <span>·</span>
                            <span>📷 {{ $vehiculo->fotos_count }}</span>
                        @endif
                    </div>

                    @can('ver precios compra')
                        <div class="mt-2 text-sm text-gray-500">
                            Compra: <span class="font-semibold text-gray-700">{{ dinero($vehiculo->precio_compra) }}</span>
                        </div>
                    @endcan
                </a>
            @empty
                <div class="col-span-full bg-white shadow rounded-xl p-8 text-center text-gray-500">
                    No hay vehículos que coincidan con la búsqueda.
                </div>
            @endforelse
        </div>

        <div>
            {{ $vehiculos->links() }}
        </div>
    </div>
</div>

<div class="py-6">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">

        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800">{{ __('Reporte de ganancias') }}</h2>

            <div class="flex flex-wrap items-center gap-2">
                <input type="month" wire:model.live="mes"
                       class="border-gray-300 rounded-xl text-base py-2.5 focus:border-blue-500 focus:ring-blue-500">

                <a href="{{ route('exportar.ganancias', ['mes' => $mes]) }}"
                   class="px-4 py-2.5 bg-green-700 hover:bg-green-800 text-white text-sm font-semibold rounded-xl shadow">
                    {{ __('⬇ Exportar Excel') }}
                </a>
            </div>
        </div>

        {{-- Resumen del mes --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
            <div class="bg-white shadow rounded-xl p-4">
                <div class="text-sm text-gray-500">{{ __('Salidas en :mes', ['mes' => $nombreMes]) }}</div>
                <div class="text-2xl font-extrabold text-gray-900 mt-1">{{ $salidas->count() }}</div>
            </div>
            <div class="bg-white shadow rounded-xl p-4">
                <div class="text-sm text-gray-500">{{ __('Recuperado en el mes') }}</div>
                <div class="text-2xl font-extrabold text-gray-900 mt-1">{{ dinero($totalRecuperado) }}</div>
            </div>
            <div class="bg-white shadow rounded-xl p-4 border-s-4 {{ $totalMes >= 0 ? 'border-green-500' : 'border-red-500' }}">
                <div class="text-sm text-gray-500">{{ __('Ganancia de :mes', ['mes' => $nombreMes]) }}</div>
                <div class="text-2xl font-extrabold mt-1 {{ $totalMes >= 0 ? 'text-green-700' : 'text-red-700' }}">{{ dinero($totalMes) }}</div>
            </div>
            <div class="bg-white shadow rounded-xl p-4 border-s-4 {{ $acumulada >= 0 ? 'border-green-500' : 'border-red-500' }}">
                <div class="text-sm text-gray-500">{{ __('Ganancia acumulada') }}</div>
                <div class="text-2xl font-extrabold mt-1 {{ $acumulada >= 0 ? 'text-green-700' : 'text-red-700' }}">{{ dinero($acumulada) }}</div>
            </div>
        </div>

        <p class="text-sm text-gray-500">
            💼 {{ __('Capital invertido en el inventario actual:') }} <span class="font-semibold text-gray-800">{{ dinero($invertidoInventario) }}</span>
        </p>

        {{-- Tabla (móvil: tarjetas) --}}
        <div class="bg-white shadow rounded-xl overflow-hidden">
            {{-- Vista móvil --}}
            <div class="sm:hidden divide-y divide-gray-100">
                @forelse ($salidas as $salida)
                    <a href="{{ route('vehiculos.ficha', $salida['vehiculo']) }}" class="block p-4">
                        <div class="flex items-center justify-between">
                            <span class="font-semibold text-gray-900">{{ $salida['vehiculo']->nombreCompleto() }}</span>
                            <span class="font-bold {{ $salida['ganancia'] >= 0 ? 'text-green-700' : 'text-red-700' }}">{{ dinero($salida['ganancia']) }}</span>
                        </div>
                        <div class="text-xs text-gray-500 mt-1">
                            {{ __($salida['tipo']) }} · {{ $salida['fecha']->format('d/m/Y') }} ·
                            {{ __('compra') }} {{ dinero($salida['compra']) }} · {{ __('gastos') }} {{ dinero($salida['gastos']) }} · {{ __('recibido') }} {{ dinero($salida['recuperado']) }}
                        </div>
                    </a>
                @empty
                    <p class="p-6 text-center text-gray-500 text-sm">{{ __('Sin ventas ni desguaces en :mes.', ['mes' => $nombreMes]) }}</p>
                @endforelse
            </div>

            {{-- Vista escritorio --}}
            <div class="hidden sm:block overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">
                            <th class="px-4 py-3">{{ __('Vehículo') }}</th>
                            <th class="px-4 py-3">{{ __('Tipo') }}</th>
                            <th class="px-4 py-3">{{ __('Fecha') }}</th>
                            <th class="px-4 py-3 text-right">{{ __('Compra') }}</th>
                            <th class="px-4 py-3 text-right">{{ __('Gastos') }}</th>
                            <th class="px-4 py-3 text-right">{{ __('Recuperado') }}</th>
                            <th class="px-4 py-3 text-right">{{ __('Ganancia') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($salidas as $salida)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    <a href="{{ route('vehiculos.ficha', $salida['vehiculo']) }}" class="font-medium text-blue-700 hover:underline">
                                        {{ $salida['vehiculo']->nombreCompleto() }}
                                    </a>
                                    <div class="text-xs text-gray-400 font-mono">{{ $salida['vehiculo']->vin }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ $salida['tipo'] === 'Venta' ? 'bg-blue-100 text-blue-800' : 'bg-gray-200 text-gray-700' }}">
                                        {{ __($salida['tipo']) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">{{ $salida['fecha']->format('d/m/Y') }}</td>
                                <td class="px-4 py-3 text-right">{{ dinero($salida['compra']) }}</td>
                                <td class="px-4 py-3 text-right">{{ dinero($salida['gastos']) }}</td>
                                <td class="px-4 py-3 text-right">{{ dinero($salida['recuperado']) }}</td>
                                <td class="px-4 py-3 text-right font-bold {{ $salida['ganancia'] >= 0 ? 'text-green-700' : 'text-red-700' }}">
                                    {{ dinero($salida['ganancia']) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-gray-500">{{ __('Sin ventas ni desguaces en :mes.', ['mes' => $nombreMes]) }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if ($salidas->isNotEmpty())
                        <tfoot class="bg-gray-50 font-semibold">
                            <tr>
                                <td class="px-4 py-3" colspan="3">{{ __('Totales de :mes', ['mes' => $nombreMes]) }}</td>
                                <td class="px-4 py-3 text-right" colspan="2">{{ dinero($totalInvertidoMes) }}</td>
                                <td class="px-4 py-3 text-right">{{ dinero($totalRecuperado) }}</td>
                                <td class="px-4 py-3 text-right {{ $totalMes >= 0 ? 'text-green-700' : 'text-red-700' }}">{{ dinero($totalMes) }}</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
</div>

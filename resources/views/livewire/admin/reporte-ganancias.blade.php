<div class="py-6">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">

        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-bold text-lg text-slate-800 flex items-center gap-2">
                <x-icono nombre="reportes" clase="w-6 h-6 text-blue-600" /> {{ __('Reporte de ganancias') }}
            </h2>

            <div class="flex flex-wrap items-center gap-2">
                <input type="month" wire:model.live="mes" class="campo !w-auto py-2">
                <a href="{{ route('exportar.ganancias', ['mes' => $mes]) }}" class="btn-secundario btn-sm text-green-700 border-green-200 hover:bg-green-50">
                    <x-icono nombre="descargar" clase="w-4 h-4" /> {{ __('Exportar Excel') }}
                </a>
            </div>
        </div>

        {{-- Salidas que todavía no se pueden valorar --}}
        @if ($pendientes->isNotEmpty())
            <div class="rounded-2xl bg-amber-50 ring-1 ring-amber-200 p-4">
                <div class="flex items-start gap-2.5">
                    <x-icono nombre="reloj" clase="w-5 h-5 text-amber-600 shrink-0 mt-0.5" />
                    <div class="min-w-0">
                        <p class="font-bold text-amber-900">
                            {{ trans_choice('{1} :n salida pendiente de valorar|[2,*] :n salidas pendientes de valorar', $pendientes->count(), ['n' => $pendientes->count()]) }}
                        </p>
                        <p class="text-sm text-amber-800 mt-0.5">
                            {{ __('No se incluyen en la ganancia hasta que se complete el dato que falta.') }}
                        </p>

                        <ul class="mt-2.5 space-y-1.5">
                            @foreach ($pendientes as $s)
                                <li class="text-sm">
                                    <a href="{{ route('vehiculos.ficha', $s['vehiculo']) }}" wire:navigate
                                       class="font-semibold text-amber-900 hover:underline">
                                        {{ $s['vehiculo']->nombreCompleto() }}
                                    </a>
                                    <span class="text-amber-700">
                                        · {{ $s['motivo'] === 'sin_monto_junk' ? __('Falta el monto del Junk car') : __('Falta el precio de compra') }}
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        {{-- Resumen del mes --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
            <div class="tarjeta p-4">
                <div class="etiqueta-seccion">{{ __('Salidas en :mes', ['mes' => $nombreMes]) }}</div>
                <div class="text-2xl font-extrabold text-slate-900 mt-1 tabular">{{ $salidas->count() }}</div>
            </div>
            <div class="tarjeta p-4">
                <div class="etiqueta-seccion">{{ __('Recuperado en el mes') }}</div>
                <div class="text-2xl font-extrabold text-slate-900 mt-1 tabular">{{ dinero($totalRecuperado) }}</div>
            </div>
            <div class="tarjeta p-4 border-l-4 {{ $totalMes >= 0 ? 'border-l-green-500' : 'border-l-red-500' }}">
                <div class="etiqueta-seccion">{{ __('Ganancia de :mes', ['mes' => $nombreMes]) }}</div>
                <div class="text-2xl font-extrabold mt-1 tabular {{ $totalMes >= 0 ? 'text-green-700' : 'text-red-700' }}">{{ dinero($totalMes) }}</div>
            </div>
            <div class="tarjeta p-4 border-l-4 {{ $acumulada >= 0 ? 'border-l-green-500' : 'border-l-red-500' }}">
                <div class="etiqueta-seccion">{{ __('Ganancia acumulada') }}</div>
                <div class="text-2xl font-extrabold mt-1 tabular {{ $acumulada >= 0 ? 'text-green-700' : 'text-red-700' }}">{{ dinero($acumulada) }}</div>
            </div>
        </div>

        <p class="text-sm text-slate-500 flex items-center gap-2">
            <x-icono nombre="archivo" clase="w-4 h-4 text-slate-400" />
            {{ __('Capital invertido en el inventario actual:') }} <span class="font-semibold text-slate-800 tabular">{{ dinero($invertidoInventario) }}</span>
        </p>

        {{-- Tabla (móvil: tarjetas) --}}
        <div class="tarjeta overflow-hidden">
            {{-- Vista móvil --}}
            <div class="sm:hidden divide-y divide-slate-100">
                @forelse ($salidas as $salida)
                    <a href="{{ route('vehiculos.ficha', $salida['vehiculo']) }}" class="block p-4">
                        <div class="flex items-center justify-between gap-2">
                            <span class="font-semibold text-slate-900 truncate">{{ $salida['vehiculo']->nombreCompleto() }}</span>
                            <span class="font-bold tabular {{ $salida['ganancia'] >= 0 ? 'text-green-700' : 'text-red-700' }}">{{ dinero($salida['ganancia']) }}</span>
                        </div>
                        <div class="text-xs text-slate-400 mt-1 tabular">
                            {{ __($salida['tipo']) }} · {{ $salida['fecha']->format('d/m/Y') }} ·
                            {{ __('compra') }} {{ dinero($salida['compra']) }} · {{ __('gastos') }} {{ dinero($salida['gastos']) }} · {{ __('recibido') }} {{ dinero($salida['recuperado']) }}
                        </div>
                    </a>
                @empty
                    <p class="p-8 text-center text-slate-500 text-sm">{{ __('Sin ventas ni Junk car en :mes.', ['mes' => $nombreMes]) }}</p>
                @endforelse
            </div>

            {{-- Vista escritorio --}}
            <div class="hidden sm:block overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">
                            <th class="px-4 py-3">{{ __('Vehículo') }}</th>
                            <th class="px-4 py-3">{{ __('Tipo') }}</th>
                            <th class="px-4 py-3">{{ __('Fecha') }}</th>
                            <th class="px-4 py-3 text-right">{{ __('Compra') }}</th>
                            <th class="px-4 py-3 text-right">{{ __('Gastos') }}</th>
                            <th class="px-4 py-3 text-right">{{ __('Recuperado') }}</th>
                            <th class="px-4 py-3 text-right">{{ __('Ganancia') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($salidas as $salida)
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3">
                                    <a href="{{ route('vehiculos.ficha', $salida['vehiculo']) }}" class="font-medium text-blue-700 hover:underline">
                                        {{ $salida['vehiculo']->nombreCompleto() }}
                                    </a>
                                    <div class="text-xs text-slate-400 font-mono">{{ $salida['vehiculo']->vin }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="chip {{ $salida['tipo'] === 'Venta' ? 'bg-blue-100 text-blue-800' : 'bg-slate-200 text-slate-700' }}">
                                        {{ __($salida['tipo']) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 tabular">{{ $salida['fecha']->format('d/m/Y') }}</td>
                                <td class="px-4 py-3 text-right tabular">{{ dinero($salida['compra']) }}</td>
                                <td class="px-4 py-3 text-right tabular">{{ dinero($salida['gastos']) }}</td>
                                <td class="px-4 py-3 text-right tabular">{{ dinero($salida['recuperado']) }}</td>
                                <td class="px-4 py-3 text-right font-bold tabular {{ $salida['ganancia'] >= 0 ? 'text-green-700' : 'text-red-700' }}">
                                    {{ dinero($salida['ganancia']) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-10 text-center text-slate-500">{{ __('Sin ventas ni Junk car en :mes.', ['mes' => $nombreMes]) }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if ($salidas->isNotEmpty())
                        <tfoot class="bg-slate-50 font-semibold">
                            <tr>
                                <td class="px-4 py-3" colspan="3">{{ __('Totales de :mes', ['mes' => $nombreMes]) }}</td>
                                <td class="px-4 py-3 text-right tabular" colspan="2">{{ dinero($totalInvertidoMes) }}</td>
                                <td class="px-4 py-3 text-right tabular">{{ dinero($totalRecuperado) }}</td>
                                <td class="px-4 py-3 text-right tabular {{ $totalMes >= 0 ? 'text-green-700' : 'text-red-700' }}">{{ dinero($totalMes) }}</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
</div>

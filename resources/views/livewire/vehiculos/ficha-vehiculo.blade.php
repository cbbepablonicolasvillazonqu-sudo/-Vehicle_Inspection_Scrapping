<div class="py-6">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">

        {{-- Encabezado --}}
        <div class="bg-white shadow rounded-xl p-4 sm:p-6">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="min-w-0">
                    <a href="{{ route('vehiculos.index') }}" class="text-sm text-blue-700 hover:underline">← Volver a vehículos</a>
                    <h2 class="font-bold text-2xl text-gray-900 mt-1">{{ $vehiculo->nombreCompleto() }}</h2>
                    <div class="text-sm text-gray-500 font-mono mt-1">VIN: {{ $vehiculo->vin }}</div>
                </div>

                <span class="px-4 py-2 rounded-xl text-sm font-bold {{ $vehiculo->estado->colorBadge() }}">
                    {{ $vehiculo->estado->etiqueta() }}
                </span>
            </div>

            {{-- Datos generales --}}
            <dl class="mt-4 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-x-4 gap-y-3 text-sm">
                <div>
                    <dt class="text-gray-500">Millas</dt>
                    <dd class="font-semibold text-gray-900">{{ number_format($vehiculo->millas) }} mi</dd>
                </div>

                @can('ver precios compra')
                    <div>
                        <dt class="text-gray-500">Precio de compra</dt>
                        <dd class="font-semibold text-gray-900">{{ dinero($vehiculo->precio_compra) }}</dd>
                    </div>
                @endcan

                <div>
                    <dt class="text-gray-500">Fecha de compra</dt>
                    <dd class="font-semibold text-gray-900">{{ $vehiculo->fecha_compra->format('d/m/Y') }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Lugar de compra</dt>
                    <dd class="font-semibold text-gray-900">{{ $vehiculo->lugar_compra->etiqueta() }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Título</dt>
                    <dd><span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ $vehiculo->estado_titulo->colorBadge() }}">{{ $vehiculo->estado_titulo->etiqueta() }}</span></dd>
                </div>
                <div>
                    <dt class="text-gray-500">Registrado por</dt>
                    <dd class="font-semibold text-gray-900">{{ $vehiculo->creador?->name ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Registrado el</dt>
                    <dd class="font-semibold text-gray-900">{{ $vehiculo->created_at->format('d/m/Y') }}</dd>
                </div>

                @can('registrar gastos')
                    <div>
                        <dt class="text-gray-500">Gastos totales</dt>
                        <dd class="font-semibold text-gray-900">{{ dinero($vehiculo->totalGastos()) }}</dd>
                    </div>
                @endcan

                @can('ver precios compra')
                    <div>
                        <dt class="text-gray-500">Inversión total</dt>
                        <dd class="font-semibold text-gray-900">{{ dinero($vehiculo->inversionTotal()) }}</dd>
                    </div>
                @endcan

                @can('ver ganancias')
                    @if (! is_null($vehiculo->ganancia()))
                        <div>
                            <dt class="text-gray-500">Ganancia</dt>
                            <dd class="font-bold {{ $vehiculo->ganancia() >= 0 ? 'text-green-700' : 'text-red-700' }}">
                                {{ dinero($vehiculo->ganancia()) }}
                            </dd>
                        </div>
                    @endif
                @endcan
            </dl>

            @if ($vehiculo->notas)
                <div class="mt-4 bg-gray-50 border border-gray-200 rounded-lg p-3 text-sm text-gray-700 whitespace-pre-line">{{ $vehiculo->notas }}</div>
            @endif

            {{-- Acciones --}}
            <div class="mt-4 flex flex-wrap gap-3">
                @can('update', $vehiculo)
                    @hasanyrole('admin|comprador')
                        <a href="{{ route('vehiculos.editar', $vehiculo) }}"
                           class="px-4 py-3 bg-white border border-gray-300 rounded-xl text-base font-medium text-gray-700 hover:bg-gray-50">
                            ✏️ Editar datos
                        </a>
                    @endhasanyrole
                @endcan

                @can('delete', $vehiculo)
                    <button wire:click="eliminar"
                            wire:confirm="¿Eliminar este vehículo y todo su historial? Esta acción solo la puede hacer el Admin."
                            class="px-4 py-3 bg-red-50 border border-red-200 rounded-xl text-base font-medium text-red-700 hover:bg-red-100">
                        🗑️ Eliminar
                    </button>
                @endcan
            </div>

            @if ($vehiculo->estaBloqueado() && ! auth()->user()->hasRole('admin'))
                <p class="mt-3 text-sm text-gray-500 bg-gray-50 border border-gray-200 rounded-lg p-3">
                    🔒 Este vehículo está {{ $vehiculo->estado->etiquetaCorta() === 'Vendido' ? 'vendido' : 'en desguace' }} y el registro quedó bloqueado. Solo el Administrador puede modificarlo.
                </p>
            @endif
        </div>

        {{-- Estado y transiciones --}}
        <livewire:vehiculos.gestor-estado :vehiculo="$vehiculo" :key="'estado-'.$vehiculo->id" />

        {{-- Venta (formulario para Vendedor/Admin; tarjeta si ya se vendió) --}}
        <livewire:vehiculos.gestor-venta :vehiculo="$vehiculo" :key="'venta-'.$vehiculo->id" />

        {{-- Desguace (solo Admin registra; tarjeta si ya se desguazó) --}}
        <livewire:vehiculos.gestor-desguace :vehiculo="$vehiculo" :key="'desguace-'.$vehiculo->id" />

        {{-- Gastos (Admin, Comprador y Mecánico) --}}
        @can('registrar gastos')
            <livewire:vehiculos.gestor-gastos :vehiculo="$vehiculo" :key="'gastos-'.$vehiculo->id" />
        @endcan

        {{-- Fotos por etapa --}}
        <livewire:vehiculos.gestor-fotos :vehiculo="$vehiculo" :key="'fotos-'.$vehiculo->id" />

        {{-- Auditoría (solo Admin) --}}
        @role('admin')
            <div class="bg-white shadow rounded-xl p-4 sm:p-6" x-data="{ abierto: false }">
                <button type="button" @click="abierto = !abierto" class="w-full flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-800">Auditoría del vehículo</h3>
                    <span class="text-gray-400" x-text="abierto ? '▲' : '▼'"></span>
                </button>

                <div x-show="abierto" x-collapse.duration.200ms class="mt-3 divide-y divide-gray-100" style="display: none;">
                    @forelse ($auditoria as $registro)
                        <div class="py-2.5 text-sm">
                            <div class="flex flex-wrap items-center gap-x-2">
                                <span class="font-semibold text-gray-800">{{ $registro->etiquetaAccion() }}</span>
                                <span class="text-gray-500">· {{ $registro->usuario?->name ?? 'Sistema' }}</span>
                                <span class="text-gray-400">· {{ $registro->created_at->format('d/m/Y H:i') }}</span>
                            </div>
                            @if ($registro->detalles)
                                <div class="mt-1 text-xs text-gray-500 break-words">
                                    @if (isset($registro->detalles['cambios']))
                                        @foreach ($registro->detalles['cambios'] as $campo => $cambio)
                                            <div>{{ $campo }}: <s>{{ $cambio['antes'] ?? '—' }}</s> → <span class="text-gray-700">{{ $cambio['despues'] ?? '—' }}</span></div>
                                        @endforeach
                                    @else
                                        {{ collect($registro->detalles)->map(fn ($v, $k) => is_scalar($v) ? "$k: $v" : null)->filter()->implode(' · ') }}
                                    @endif
                                </div>
                            @endif
                        </div>
                    @empty
                        <p class="py-3 text-sm text-gray-500">Sin registros de auditoría.</p>
                    @endforelse
                </div>
            </div>
        @endrole

    </div>
</div>

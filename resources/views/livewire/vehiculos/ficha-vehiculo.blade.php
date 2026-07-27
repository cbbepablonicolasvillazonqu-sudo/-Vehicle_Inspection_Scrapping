<div class="py-6">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">

        <a href="{{ route('vehiculos.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 hover:text-slate-800">
            <x-icono nombre="flecha-izq" clase="w-4 h-4" /> {{ __('← Volver a vehículos') }}
        </a>

        {{-- Encabezado con miniatura --}}
        <div class="tarjeta overflow-hidden">
            <div class="flex flex-col sm:flex-row">
                {{-- Miniatura de portada --}}
                <div class="relative sm:w-56 shrink-0 bg-slate-100 aspect-video sm:aspect-auto">
                    @if ($vehiculo->fotoPortada)
                        <img src="{{ $vehiculo->fotoPortada->url() }}" alt="{{ $vehiculo->nombreCompleto() }}"
                             class="absolute inset-0 w-full h-full object-cover">
                    @else
                        <div class="absolute inset-0 grid place-items-center text-slate-300">
                            <x-icono nombre="sin-foto" clase="w-12 h-12" />
                        </div>
                    @endif
                </div>

                {{-- Título y estado --}}
                <div class="flex-1 p-4 sm:p-6">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h2 class="font-extrabold text-2xl text-slate-900 leading-tight">{{ $vehiculo->nombreCompleto() }}</h2>
                            <div class="text-sm text-slate-400 font-mono mt-1">{{ __('VIN:') }} {{ $vehiculo->vin }}</div>
                        </div>
                        <span class="chip text-sm px-3.5 py-1.5 {{ $vehiculo->estado->colorBadge() }}">
                            <span class="w-2 h-2 rounded-full {{ $vehiculo->estado->colorPunto() }}"></span>
                            {{ $vehiculo->estado->etiqueta() }}
                        </span>
                    </div>

                    {{-- Acciones --}}
                    <div class="mt-4 flex flex-wrap gap-2">
                        @can('update', $vehiculo)
                            @hasanyrole('admin')
                                <a href="{{ route('vehiculos.editar', $vehiculo) }}" class="btn-secundario btn-sm">
                                    <x-icono nombre="editar" clase="w-4 h-4" /> {{ __('Editar datos') }}
                                </a>
                            @endhasanyrole
                        @endcan

                        @can('delete', $vehiculo)
                            <button wire:click="eliminar"
                                    wire:confirm="{{ __('¿Eliminar este vehículo y todo su historial? Esta acción solo la puede hacer el Admin.') }}"
                                    class="btn-peligro btn-sm">
                                <x-icono nombre="basura" clase="w-4 h-4" /> {{ __('Eliminar') }}
                            </button>
                        @endcan
                    </div>
                </div>
            </div>

            {{-- Datos generales --}}
            <div class="border-t border-slate-100 p-4 sm:p-6">
                <dl class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-x-4 gap-y-4 text-sm">
                    <div>
                        <dt class="text-slate-400 text-xs">{{ __('Millas') }}</dt>
                        <dd class="font-semibold text-slate-900 tabular">{{ $vehiculo->millas !== null ? number_format($vehiculo->millas).' '.__('mi') : '—' }}</dd>
                    </div>

                    @can('ver precios compra')
                        <div>
                            <dt class="text-slate-400 text-xs">{{ __('Precio de compra') }}</dt>
                            <dd class="font-semibold text-slate-900 tabular">{{ dinero($vehiculo->precio_compra) }}</dd>
                        </div>
                    @endcan

                    <div>
                        <dt class="text-slate-400 text-xs">{{ __('Fecha de compra') }}</dt>
                        <dd class="font-semibold text-slate-900">{{ $vehiculo->fecha_compra?->format('d/m/Y') ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-400 text-xs">{{ __('Lugar de compra') }}</dt>
                        <dd class="font-semibold text-slate-900">{{ $vehiculo->lugar_compra?->etiqueta() ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-400 text-xs">{{ __('Título') }}</dt>
                        <dd>
                            @if ($vehiculo->estado_titulo)
                                <span class="chip {{ $vehiculo->estado_titulo->colorBadge() }}">{{ $vehiculo->estado_titulo->etiqueta() }}</span>
                            @else
                                <span class="text-slate-300">—</span>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-slate-400 text-xs">{{ __('Registrado por') }}</dt>
                        <dd class="font-semibold text-slate-900">{{ $vehiculo->creador?->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-400 text-xs">{{ __('Registrado el') }}</dt>
                        <dd class="font-semibold text-slate-900">{{ $vehiculo->created_at->format('d/m/Y') }}</dd>
                    </div>

                    <div>
                        <dt class="text-slate-400 text-xs">{{ __('Precio de venta sugerido') }}</dt>
                        <dd class="font-semibold tabular {{ $vehiculo->precio_sugerido !== null ? 'text-blue-800' : 'text-slate-300' }}">
                            {{ $vehiculo->precio_sugerido !== null ? dinero($vehiculo->precio_sugerido) : __('Sin definir') }}
                        </dd>
                    </div>

                    @can('registrar gastos')
                        <div>
                            <dt class="text-slate-400 text-xs">{{ __('Gastos totales') }}</dt>
                            <dd class="font-semibold text-slate-900 tabular">{{ dinero($vehiculo->totalGastos()) }}</dd>
                        </div>
                    @endcan

                    @can('ver precios compra')
                        <div>
                            <dt class="text-slate-400 text-xs">{{ __('Inversión total') }}</dt>
                            <dd class="font-semibold text-slate-900 tabular">{{ dinero($vehiculo->inversionTotal()) }}</dd>
                        </div>
                    @endcan

                    @can('ver ganancias')
                        @if (! is_null($vehiculo->ganancia()))
                            <div>
                                <dt class="text-slate-400 text-xs">{{ __('Ganancia') }}</dt>
                                <dd class="font-bold tabular {{ $vehiculo->ganancia() >= 0 ? 'text-green-700' : 'text-red-700' }}">
                                    {{ dinero($vehiculo->ganancia()) }}
                                </dd>
                            </div>
                        @endif
                    @endcan
                </dl>

                {{-- Fijar precio de venta sugerido (solo Admin) --}}
                @can('fijar precio venta')
                    <form wire:submit="guardarPrecioSugerido"
                          class="mt-5 flex flex-wrap items-end gap-3 bg-blue-50/70 border border-blue-200 rounded-xl p-3.5">
                        <div>
                            <x-input-label for="precioSugerido" :value="__('Precio de venta sugerido (USD)')" />
                            <x-text-input id="precioSugerido" type="number" step="0.01" inputmode="decimal"
                                          class="w-44" wire:model="precioSugerido" placeholder="4500.00" />
                        </div>
                        <button type="submit" class="btn-primario btn-sm" wire:loading.attr="disabled" wire:target="guardarPrecioSugerido">
                            <x-icono nombre="etiqueta" clase="w-4 h-4" /> {{ __('Guardar precio') }}
                        </button>
                        <x-input-error :messages="$errors->get('precioSugerido')" class="w-full" />
                        <p class="w-full text-xs text-slate-500 m-0">{{ __('Referencia visible para el Vendedor al negociar. Dejar vacío para quitarlo.') }}</p>
                    </form>
                @endcan

                @if ($vehiculo->notas)
                    <div class="mt-4 bg-slate-50 border border-slate-200 rounded-xl p-3.5 text-sm text-slate-700 whitespace-pre-line">{{ $vehiculo->notas }}</div>
                @endif

                @if ($vehiculo->estaBloqueado() && ! auth()->user()->hasRole('admin'))
                    <div class="mt-4 flex items-start gap-2.5 text-sm text-slate-600 bg-slate-50 border border-slate-200 rounded-xl p-3.5">
                        <x-icono nombre="candado" clase="w-5 h-5 shrink-0 text-slate-400" />
                        <span>
                            @if ($vehiculo->estado === \App\Enums\EstadoVehiculo::Vendido)
                                {{ __('Este vehículo está vendido y el registro quedó bloqueado. Solo el Administrador puede modificarlo.') }}
                            @else
                                {{ __('Este vehículo está en Junk car y el registro quedó bloqueado. Solo el Administrador puede modificarlo.') }}
                            @endif
                        </span>
                    </div>
                @endif
            </div>
        </div>

        {{-- Recojo: ubicación de origen, pago, destino, catalizador y monto --}}
        @if ($vehiculo->ubicacion_origen_url || $vehiculo->asignado_a || auth()->user()->hasRole(['admin', 'gruero']))
            <livewire:vehiculos.gestor-recojo :vehiculo="$vehiculo" :key="'recojo-'.$vehiculo->id" />
        @endif

        {{-- Estado y transiciones --}}
        <livewire:vehiculos.gestor-estado :vehiculo="$vehiculo" :key="'estado-'.$vehiculo->id" />

        {{-- Venta (formulario para Vendedor/Admin; tarjeta si ya se vendió) --}}
        <livewire:vehiculos.gestor-venta :vehiculo="$vehiculo" :key="'venta-'.$vehiculo->id" />

        {{-- Desguace (solo Admin registra; tarjeta si ya se desguazó) --}}
        <livewire:vehiculos.gestor-desguace :vehiculo="$vehiculo" :key="'desguace-'.$vehiculo->id" />

        {{-- Gastos, con sus fotos (Admin y Mecánico). Único módulo con fotos. --}}
        @can('registrar gastos')
            <livewire:vehiculos.gestor-gastos :vehiculo="$vehiculo" :key="'gastos-'.$vehiculo->id" />
        @endcan

        {{-- Auditoría (solo Admin) --}}
        @role('admin')
            <div class="tarjeta p-4 sm:p-6" x-data="{ abierto: false }">
                <button type="button" @click="abierto = !abierto" class="w-full flex items-center justify-between">
                    <h3 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                        <x-icono nombre="reloj" clase="w-5 h-5 text-slate-400" /> {{ __('Auditoría del vehículo') }}
                    </h3>
                    <span class="text-slate-400 transition" :class="abierto && 'rotate-180'">▾</span>
                </button>

                <div x-show="abierto" x-collapse.duration.200ms class="mt-3 divide-y divide-slate-100" style="display: none;">
                    @forelse ($auditoria as $registro)
                        <div class="py-2.5 text-sm">
                            <div class="flex flex-wrap items-center gap-x-2">
                                <span class="font-semibold text-slate-800">{{ $registro->etiquetaAccion() }}</span>
                                <span class="text-slate-500">· {{ $registro->usuario?->name ?? __('Sistema') }}</span>
                                <span class="text-slate-400">· {{ $registro->created_at->format('d/m/Y H:i') }}</span>
                            </div>
                            @if ($registro->detalles)
                                <div class="mt-1 text-xs text-slate-500 break-words">
                                    @if (isset($registro->detalles['cambios']))
                                        @foreach ($registro->detalles['cambios'] as $campo => $cambio)
                                            <div>{{ $campo }}: <s>{{ $cambio['antes'] ?? '—' }}</s> → <span class="text-slate-700">{{ $cambio['despues'] ?? '—' }}</span></div>
                                        @endforeach
                                    @else
                                        {{ collect($registro->detalles)->map(fn ($v, $k) => is_scalar($v) ? "$k: $v" : null)->filter()->implode(' · ') }}
                                    @endif
                                </div>
                            @endif
                        </div>
                    @empty
                        <p class="py-3 text-sm text-slate-500">{{ __('Sin registros de auditoría.') }}</p>
                    @endforelse
                </div>
            </div>
        @endrole

    </div>
</div>

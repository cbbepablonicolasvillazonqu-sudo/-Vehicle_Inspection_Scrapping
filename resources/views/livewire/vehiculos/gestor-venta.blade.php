<div>
    @if ($venta)
        {{-- Venta cerrada --}}
        <div class="tarjeta p-4 sm:p-6 border-t-4 border-t-blue-600">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h3 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                    <x-icono nombre="dinero" clase="w-5 h-5 text-blue-600" /> {{ __('Venta') }}
                </h3>

                @if ($this->puedeEditarCerrada() && ! $editando)
                    <div class="flex gap-2">
                        <button wire:click="editar" class="btn-secundario btn-sm">
                            <x-icono nombre="editar" clase="w-4 h-4" /> {{ __('Editar venta (Admin)') }}
                        </button>
                        <button wire:click="eliminarVenta"
                                wire:confirm="{{ __('¿Eliminar la venta? El vehículo volverá a su estado anterior.') }}"
                                class="btn-peligro btn-sm">
                            <x-icono nombre="basura" clase="w-4 h-4" /> {{ __('Eliminar venta') }}
                        </button>
                    </div>
                @endif
            </div>

            @if (! $editando)
                <dl class="mt-4 grid grid-cols-2 sm:grid-cols-3 gap-x-4 gap-y-4 text-sm">
                    <div>
                        <dt class="text-slate-400 text-xs">{{ __('Precio de venta') }}</dt>
                        <dd class="font-bold text-slate-900 text-lg tabular">{{ dinero($venta->precio_venta) }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-400 text-xs">{{ __('Fecha') }}</dt>
                        <dd class="font-semibold text-slate-900">{{ $venta->fecha_venta->format('d/m/Y') }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-400 text-xs">{{ __('Método de pago') }}</dt>
                        <dd class="font-semibold text-slate-900">{{ $venta->metodo_pago->etiqueta() }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-400 text-xs">{{ __('Comprador') }}</dt>
                        <dd class="font-semibold text-slate-900">{{ $venta->nombre_comprador }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-400 text-xs">{{ __('Teléfono') }}</dt>
                        <dd><a href="tel:{{ $venta->telefono_comprador }}" class="font-semibold text-blue-700 hover:underline inline-flex items-center gap-1">{{ $venta->telefono_comprador }}</a></dd>
                    </div>
                    <div>
                        <dt class="text-slate-400 text-xs">{{ __('Correo electrónico') }}</dt>
                        <dd>
                            @if ($venta->email_comprador)
                                <a href="mailto:{{ $venta->email_comprador }}" class="font-semibold text-blue-700 hover:underline break-all">{{ $venta->email_comprador }}</a>
                            @else
                                <span class="text-slate-300">—</span>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-slate-400 text-xs">{{ __('Contrato') }}</dt>
                        <dd>
                            @if ($venta->contratoUrl())
                                <a href="{{ $venta->contratoUrl() }}" target="_blank" rel="noopener"
                                   class="font-semibold text-blue-700 hover:underline inline-flex items-center gap-1">
                                    <x-icono nombre="archivo" clase="w-4 h-4" />
                                    {{ $venta->contratoEsPdf() ? __('Ver PDF') : __('Ver foto') }}
                                </a>
                            @else
                                <span class="text-slate-300">—</span>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-slate-400 text-xs">{{ __('Registrada por') }}</dt>
                        <dd class="font-semibold text-slate-900">{{ $venta->usuario?->name ?? '—' }}</dd>
                    </div>

                    @can('ver ganancias')
                        @if (! is_null($vehiculo->ganancia()))
                            <div class="col-span-2 sm:col-span-3 mt-1 bg-slate-50 border border-slate-200 rounded-xl p-3.5">
                                <span class="text-slate-500 text-sm">{{ __('Ganancia:') }}</span>
                                <span class="font-bold text-lg tabular {{ $vehiculo->ganancia() >= 0 ? 'text-green-700' : 'text-red-700' }}">
                                    {{ dinero($vehiculo->ganancia()) }}
                                </span>
                                <span class="text-xs text-slate-400 block sm:inline sm:ms-2">
                                    {{ __('(venta :venta − compra :compra − gastos :gastos)', ['venta' => dinero($venta->precio_venta), 'compra' => dinero($vehiculo->precio_compra), 'gastos' => dinero($vehiculo->totalGastos())]) }}
                                </span>
                            </div>
                        @endif
                    @endcan
                </dl>

                @if ($venta->notas)
                    <div class="mt-3 bg-slate-50 border border-slate-200 rounded-xl p-3.5 text-sm text-slate-700 whitespace-pre-line">{{ $venta->notas }}</div>
                @endif
            @endif

            {{-- Edición por Admin --}}
            @if ($editando)
                <form wire:submit="actualizar" class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4 bg-slate-50 border border-slate-200 rounded-xl p-4">
                    @include('livewire.vehiculos.partials.campos-venta')

                    <div class="sm:col-span-2 flex gap-3 justify-end">
                        <button type="button" wire:click="cancelarEdicion" class="btn-secundario btn-sm">{{ __('Cancelar') }}</button>
                        <button type="submit" class="btn-primario btn-sm">{{ __('Guardar cambios') }}</button>
                    </div>
                </form>
            @endif
        </div>
    @elseif ($this->puedeVender())
        {{-- Formulario de venta --}}
        <div class="tarjeta p-4 sm:p-6 border-t-4 border-t-blue-600">
            <h3 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                <x-icono nombre="dinero" clase="w-5 h-5 text-blue-600" /> {{ __('Registrar venta') }}
            </h3>
            <p class="mt-1 text-sm text-slate-500">
                {!! __('Al guardar, el vehículo pasará a <span class="font-semibold text-blue-700">Vendido</span> y el registro quedará bloqueado (solo Admin podrá editarlo).') !!}
            </p>

            @if ($vehiculo->precio_sugerido !== null)
                <p class="mt-3 inline-flex items-center gap-2 text-sm bg-blue-50 border border-blue-200 text-blue-800 rounded-xl px-3.5 py-2">
                    <x-icono nombre="etiqueta" clase="w-4 h-4" />
                    {{ __('Precio sugerido:') }} <span class="font-bold tabular">{{ dinero($vehiculo->precio_sugerido) }}</span>
                </p>
            @endif

            <form wire:submit="registrar" class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                @include('livewire.vehiculos.partials.campos-venta')

                <div class="sm:col-span-2 flex justify-end">
                    <button type="submit"
                            wire:confirm="{{ __('¿Confirmar la venta? El registro quedará bloqueado.') }}"
                            class="btn-primario" wire:loading.attr="disabled">
                        <x-icono nombre="check" clase="w-5 h-5" />
                        <span wire:loading.remove wire:target="registrar">{{ __('Marcar como Vendido') }}</span>
                        <span wire:loading wire:target="registrar">{{ __('Guardando…') }}</span>
                    </button>
                </div>
            </form>
        </div>
    @endif
</div>

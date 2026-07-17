<div>
    @if ($venta)
        {{-- Venta cerrada --}}
        <div class="bg-white shadow rounded-xl p-4 sm:p-6 border-t-4 border-blue-600">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h3 class="text-lg font-semibold text-gray-800">{{ __('Venta') }} 🔵</h3>

                @if ($this->puedeEditarCerrada() && ! $editando)
                    <div class="flex gap-2">
                        <button wire:click="editar"
                                class="px-4 py-2.5 bg-white border border-gray-300 rounded-xl text-sm font-medium text-gray-700 hover:bg-gray-50">
                            {{ __('Editar venta (Admin)') }}
                        </button>
                        <button wire:click="eliminarVenta"
                                wire:confirm="{{ __('¿Eliminar la venta? El vehículo volverá a su estado anterior.') }}"
                                class="px-4 py-2.5 bg-red-50 border border-red-200 rounded-xl text-sm font-medium text-red-700 hover:bg-red-100">
                            {{ __('Eliminar venta') }}
                        </button>
                    </div>
                @endif
            </div>

            @if (! $editando)
                <dl class="mt-4 grid grid-cols-2 sm:grid-cols-3 gap-x-4 gap-y-3 text-sm">
                    <div>
                        <dt class="text-gray-500">{{ __('Precio de venta') }}</dt>
                        <dd class="font-bold text-gray-900 text-lg">{{ dinero($venta->precio_venta) }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">{{ __('Fecha') }}</dt>
                        <dd class="font-semibold text-gray-900">{{ $venta->fecha_venta->format('d/m/Y') }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">{{ __('Método de pago') }}</dt>
                        <dd class="font-semibold text-gray-900">{{ $venta->metodo_pago->etiqueta() }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">{{ __('Comprador') }}</dt>
                        <dd class="font-semibold text-gray-900">{{ $venta->nombre_comprador }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">{{ __('Teléfono') }}</dt>
                        <dd><a href="tel:{{ $venta->telefono_comprador }}" class="font-semibold text-blue-700 hover:underline">{{ $venta->telefono_comprador }}</a></dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">{{ __('Registrada por') }}</dt>
                        <dd class="font-semibold text-gray-900">{{ $venta->usuario?->name ?? '—' }}</dd>
                    </div>

                    @can('ver ganancias')
                        @if (! is_null($vehiculo->ganancia()))
                            <div class="col-span-2 sm:col-span-3 mt-1 bg-gray-50 border border-gray-200 rounded-lg p-3">
                                <span class="text-gray-500">{{ __('Ganancia:') }}</span>
                                <span class="font-bold text-lg {{ $vehiculo->ganancia() >= 0 ? 'text-green-700' : 'text-red-700' }}">
                                    {{ dinero($vehiculo->ganancia()) }}
                                </span>
                                <span class="text-xs text-gray-400 block sm:inline sm:ms-2">
                                    {{ __('(venta :venta − compra :compra − gastos :gastos)', ['venta' => dinero($venta->precio_venta), 'compra' => dinero($vehiculo->precio_compra), 'gastos' => dinero($vehiculo->totalGastos())]) }}
                                </span>
                            </div>
                        @endif
                    @endcan
                </dl>

                @if ($venta->notas)
                    <div class="mt-3 bg-gray-50 border border-gray-200 rounded-lg p-3 text-sm text-gray-700 whitespace-pre-line">{{ $venta->notas }}</div>
                @endif
            @endif

            {{-- Edición por Admin --}}
            @if ($editando)
                <form wire:submit="actualizar" class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4 bg-gray-50 border border-gray-200 rounded-xl p-4">
                    @include('livewire.vehiculos.partials.campos-venta')

                    <div class="sm:col-span-2 flex gap-3 justify-end">
                        <button type="button" wire:click="cancelarEdicion"
                                class="px-4 py-3 bg-white border border-gray-300 rounded-xl text-base font-medium text-gray-700 hover:bg-gray-50">
                            {{ __('Cancelar') }}
                        </button>
                        <button type="submit"
                                class="px-5 py-3 bg-blue-700 hover:bg-blue-800 text-white text-base font-semibold rounded-xl shadow">
                            {{ __('Guardar cambios') }}
                        </button>
                    </div>
                </form>
            @endif
        </div>
    @elseif ($this->puedeVender())
        {{-- Formulario de venta --}}
        <div class="bg-white shadow rounded-xl p-4 sm:p-6 border-t-4 border-blue-600">
            <h3 class="text-lg font-semibold text-gray-800">{{ __('Registrar venta') }}</h3>
            <p class="mt-1 text-sm text-gray-500">
                {!! __('Al guardar, el vehículo pasará a <span class="font-semibold text-blue-700">Vendido</span> y el registro quedará bloqueado (solo Admin podrá editarlo).') !!}
            </p>

            @if ($vehiculo->precio_sugerido !== null)
                <p class="mt-2 text-sm bg-blue-50 border border-blue-200 text-blue-800 rounded-lg px-3 py-2 inline-block">
                    💡 {{ __('Precio sugerido:') }} <span class="font-bold">{{ dinero($vehiculo->precio_sugerido) }}</span>
                </p>
            @endif

            <form wire:submit="registrar" class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                @include('livewire.vehiculos.partials.campos-venta')

                <div class="sm:col-span-2 flex justify-end">
                    <button type="submit"
                            wire:confirm="{{ __('¿Confirmar la venta? El registro quedará bloqueado.') }}"
                            class="px-6 py-3 bg-blue-700 hover:bg-blue-800 text-white text-base font-semibold rounded-xl shadow"
                            wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="registrar">{{ __('Marcar como Vendido') }}</span>
                        <span wire:loading wire:target="registrar">{{ __('Guardando…') }}</span>
                    </button>
                </div>
            </form>
        </div>
    @endif
</div>

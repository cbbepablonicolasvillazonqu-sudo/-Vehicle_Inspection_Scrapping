<div>
    @if ($desguace)
        {{-- Desguace registrado --}}
        <div class="bg-white shadow rounded-xl p-4 sm:p-6 border-t-4 border-gray-500">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h3 class="text-lg font-semibold text-gray-800">{{ __('Desguace') }} ⚙️</h3>

                @if ($this->puedeGestionar() && ! $editando)
                    <div class="flex gap-2">
                        <button wire:click="editar"
                                class="px-4 py-2.5 bg-white border border-gray-300 rounded-xl text-sm font-medium text-gray-700 hover:bg-gray-50">
                            {{ __('Editar (Admin)') }}
                        </button>
                        <button wire:click="eliminarDesguace"
                                wire:confirm="{{ __('¿Eliminar el desguace? El vehículo volverá a su estado anterior.') }}"
                                class="px-4 py-2.5 bg-red-50 border border-red-200 rounded-xl text-sm font-medium text-red-700 hover:bg-red-100">
                            {{ __('Eliminar') }}
                        </button>
                    </div>
                @endif
            </div>

            @if (! $editando)
                <dl class="mt-4 grid grid-cols-2 sm:grid-cols-3 gap-x-4 gap-y-3 text-sm">
                    <div>
                        <dt class="text-gray-500">{{ __('Monto recibido') }}</dt>
                        <dd class="font-bold text-gray-900 text-lg">{{ dinero($desguace->monto_recibido) }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">{{ __('Fecha') }}</dt>
                        <dd class="font-semibold text-gray-900">{{ $desguace->fecha->format('d/m/Y') }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">{{ __('Empresa / lugar') }}</dt>
                        <dd class="font-semibold text-gray-900">{{ $desguace->empresa }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">{{ __('Registrado por') }}</dt>
                        <dd class="font-semibold text-gray-900">{{ $desguace->usuario?->name ?? '—' }}</dd>
                    </div>

                    @can('ver ganancias')
                        @if (! is_null($vehiculo->ganancia()))
                            <div class="col-span-2 sm:col-span-3 mt-1 bg-gray-50 border border-gray-200 rounded-lg p-3">
                                <span class="text-gray-500">{{ __('Resultado:') }}</span>
                                <span class="font-bold text-lg {{ $vehiculo->ganancia() >= 0 ? 'text-green-700' : 'text-red-700' }}">
                                    {{ dinero($vehiculo->ganancia()) }}
                                </span>
                                <span class="text-xs text-gray-400 block sm:inline sm:ms-2">
                                    {{ __('(recibido :recibido − compra :compra − gastos :gastos)', ['recibido' => dinero($desguace->monto_recibido), 'compra' => dinero($vehiculo->precio_compra), 'gastos' => dinero($vehiculo->totalGastos())]) }}
                                </span>
                            </div>
                        @endif
                    @endcan
                </dl>

                @if ($desguace->notas)
                    <div class="mt-3 bg-gray-50 border border-gray-200 rounded-lg p-3 text-sm text-gray-700 whitespace-pre-line">{{ $desguace->notas }}</div>
                @endif
            @else
                <form wire:submit="actualizar" class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4 bg-gray-50 border border-gray-200 rounded-xl p-4">
                    @include('livewire.vehiculos.partials.campos-desguace')

                    <div class="sm:col-span-2 flex gap-3 justify-end">
                        <button type="button" wire:click="cancelarEdicion"
                                class="px-4 py-3 bg-white border border-gray-300 rounded-xl text-base font-medium text-gray-700 hover:bg-gray-50">
                            {{ __('Cancelar') }}
                        </button>
                        <button type="submit"
                                class="px-5 py-3 bg-gray-700 hover:bg-gray-800 text-white text-base font-semibold rounded-xl shadow">
                            {{ __('Guardar cambios') }}
                        </button>
                    </div>
                </form>
            @endif
        </div>
    @elseif ($this->puedeEnviar())
        {{-- Enviar a desguace (Admin) --}}
        <div class="bg-white shadow rounded-xl p-4 sm:p-6 border-t-4 border-gray-500" x-data="{ abierto: false }">
            <button type="button" @click="abierto = !abierto" class="w-full flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-800">{{ __('Enviar a desguace (Admin)') }}</h3>
                <span class="text-gray-400" x-text="abierto ? '▲' : '▼'"></span>
            </button>

            <form wire:submit="registrar" x-show="abierto" x-collapse.duration.200ms style="display: none;"
                  class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                @include('livewire.vehiculos.partials.campos-desguace')

                <div class="sm:col-span-2 flex justify-end">
                    <button type="submit"
                            wire:confirm="{{ __('¿Enviar este vehículo a desguace? El registro quedará bloqueado.') }}"
                            class="px-6 py-3 bg-gray-700 hover:bg-gray-800 text-white text-base font-semibold rounded-xl shadow">
                        {{ __('Confirmar desguace') }}
                    </button>
                </div>
            </form>
        </div>
    @endif
</div>

<div>
    @if ($desguace)
        {{-- Desguace registrado --}}
        <div class="tarjeta p-4 sm:p-6 border-t-4 border-t-slate-500">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h3 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                    <x-icono nombre="engranaje" clase="w-5 h-5 text-slate-500" /> {{ __('Desguace') }}
                </h3>

                @if ($this->puedeGestionar() && ! $editando)
                    <div class="flex gap-2">
                        <button wire:click="editar" class="btn-secundario btn-sm">
                            <x-icono nombre="editar" clase="w-4 h-4" /> {{ __('Editar (Admin)') }}
                        </button>
                        <button wire:click="eliminarDesguace"
                                wire:confirm="{{ __('¿Eliminar el desguace? El vehículo volverá a su estado anterior.') }}"
                                class="btn-peligro btn-sm">
                            <x-icono nombre="basura" clase="w-4 h-4" /> {{ __('Eliminar') }}
                        </button>
                    </div>
                @endif
            </div>

            @if (! $editando)
                <dl class="mt-4 grid grid-cols-2 sm:grid-cols-3 gap-x-4 gap-y-4 text-sm">
                    <div>
                        <dt class="text-slate-400 text-xs">{{ __('Monto recibido') }}</dt>
                        <dd class="font-bold text-slate-900 text-lg tabular">{{ dinero($desguace->monto_recibido) }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-400 text-xs">{{ __('Fecha') }}</dt>
                        <dd class="font-semibold text-slate-900">{{ $desguace->fecha->format('d/m/Y') }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-400 text-xs">{{ __('Empresa / lugar') }}</dt>
                        <dd class="font-semibold text-slate-900">{{ $desguace->empresa }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-400 text-xs">{{ __('Registrado por') }}</dt>
                        <dd class="font-semibold text-slate-900">{{ $desguace->usuario?->name ?? '—' }}</dd>
                    </div>

                    @can('ver ganancias')
                        @if (! is_null($vehiculo->ganancia()))
                            <div class="col-span-2 sm:col-span-3 mt-1 bg-slate-50 border border-slate-200 rounded-xl p-3.5">
                                <span class="text-slate-500 text-sm">{{ __('Resultado:') }}</span>
                                <span class="font-bold text-lg tabular {{ $vehiculo->ganancia() >= 0 ? 'text-green-700' : 'text-red-700' }}">
                                    {{ dinero($vehiculo->ganancia()) }}
                                </span>
                                <span class="text-xs text-slate-400 block sm:inline sm:ms-2">
                                    {{ __('(recibido :recibido − compra :compra − gastos :gastos)', ['recibido' => dinero($desguace->monto_recibido), 'compra' => dinero($vehiculo->precio_compra), 'gastos' => dinero($vehiculo->totalGastos())]) }}
                                </span>
                            </div>
                        @endif
                    @endcan
                </dl>

                @if ($desguace->notas)
                    <div class="mt-3 bg-slate-50 border border-slate-200 rounded-xl p-3.5 text-sm text-slate-700 whitespace-pre-line">{{ $desguace->notas }}</div>
                @endif
            @else
                <form wire:submit="actualizar" class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4 bg-slate-50 border border-slate-200 rounded-xl p-4">
                    @include('livewire.vehiculos.partials.campos-desguace')

                    <div class="sm:col-span-2 flex gap-3 justify-end">
                        <button type="button" wire:click="cancelarEdicion" class="btn-secundario btn-sm">{{ __('Cancelar') }}</button>
                        <button type="submit" class="btn bg-slate-700 text-white hover:bg-slate-800 focus:ring-slate-500 btn-sm">{{ __('Guardar cambios') }}</button>
                    </div>
                </form>
            @endif
        </div>
    @elseif ($this->puedeEnviar())
        {{-- Enviar a desguace (Admin) --}}
        <div class="tarjeta p-4 sm:p-6 border-t-4 border-t-slate-500" x-data="{ abierto: false }">
            <button type="button" @click="abierto = !abierto" class="w-full flex items-center justify-between">
                <h3 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                    <x-icono nombre="engranaje" clase="w-5 h-5 text-slate-500" /> {{ __('Enviar a desguace (Admin)') }}
                </h3>
                <span class="text-slate-400 transition" :class="abierto && 'rotate-180'">▾</span>
            </button>

            <form wire:submit="registrar" x-show="abierto" x-collapse.duration.200ms style="display: none;"
                  class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                @include('livewire.vehiculos.partials.campos-desguace')

                <div class="sm:col-span-2 flex justify-end">
                    <button type="submit"
                            wire:confirm="{{ __('¿Enviar este vehículo a desguace? El registro quedará bloqueado.') }}"
                            class="btn bg-slate-700 text-white hover:bg-slate-800 focus:ring-slate-500">
                        <x-icono nombre="engranaje" clase="w-5 h-5" /> {{ __('Confirmar desguace') }}
                    </button>
                </div>
            </form>
        </div>
    @endif
</div>

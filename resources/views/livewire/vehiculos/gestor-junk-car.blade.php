<div>
    @if ($this->esJunkCar())
        <div class="tarjeta p-4 sm:p-6">
            <h3 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                <x-icono nombre="engranaje" clase="w-5 h-5 text-slate-400" /> {{ __('Datos del Junk car') }}
            </h3>

            @if ($this->puedeCompletar())
                <form wire:submit="guardar" class="mt-4 space-y-4">
                    <div>
                        <x-input-label :value="__('¿Tiene catalizador?')" />
                        <div class="grid grid-cols-2 gap-2 mt-1">
                            @foreach (['1' => __('Sí'), '0' => __('No')] as $valor => $etiqueta)
                                <label class="cursor-pointer">
                                    <input type="radio" wire:model="tiene_catalizador" value="{{ $valor }}" class="peer sr-only">
                                    <span class="block text-center px-3 py-3 rounded-xl ring-1 ring-slate-200 bg-slate-50 text-slate-700 font-semibold text-sm
                                                 peer-checked:bg-green-600 peer-checked:text-white peer-checked:ring-green-600 transition">
                                        {{ $etiqueta }}
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        <x-input-error :messages="$errors->get('tiene_catalizador')" />
                    </div>

                    <div>
                        <x-input-label for="monto_junk" :value="__('Monto pagado por el Junk car')" />
                        <x-text-input id="monto_junk" type="number" step="0.01" min="0" inputmode="decimal"
                                      wire:model="monto_junk" class="block w-full tabular" placeholder="0.00" />
                        <x-input-error :messages="$errors->get('monto_junk')" />
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="btn-primario" wire:loading.attr="disabled" wire:target="guardar">
                            <x-icono nombre="check" clase="w-5 h-5" />
                            <span wire:loading.remove wire:target="guardar">{{ __('Guardar datos del Junk car') }}</span>
                            <span wire:loading wire:target="guardar">{{ __('Guardando…') }}</span>
                        </button>
                    </div>
                </form>
            @else
                <dl class="mt-4 grid grid-cols-2 gap-3 text-sm">
                    <div>
                        <dt class="text-slate-400 text-xs">{{ __('Catalizador') }}</dt>
                        <dd class="font-semibold text-slate-800">
                            {{ $vehiculo->tiene_catalizador === null ? '—' : ($vehiculo->tiene_catalizador ? __('Sí') : __('No')) }}
                        </dd>
                    </div>
                    @can('ver precios compra')
                        <div>
                            <dt class="text-slate-400 text-xs">{{ __('Monto pagado por el Junk car') }}</dt>
                            <dd class="font-semibold text-slate-800 tabular">
                                {{ $vehiculo->desguace?->monto_recibido !== null ? dinero($vehiculo->desguace->monto_recibido) : '—' }}
                            </dd>
                        </div>
                    @endcan
                </dl>
            @endif
        </div>
    @endif
</div>

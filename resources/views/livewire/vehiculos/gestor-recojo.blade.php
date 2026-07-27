<div class="tarjeta p-4 sm:p-6">
    <h3 class="text-lg font-bold text-slate-800 flex items-center gap-2">
        <x-icono nombre="llave-inglesa" clase="w-5 h-5 text-slate-400" /> {{ __('Recojo') }}
    </h3>

    {{-- Ubicación de origen cargada por el Admin --}}
    @if ($vehiculo->ubicacion_origen_url)
        <a href="{{ $vehiculo->ubicacion_origen_url }}" target="_blank" rel="noopener noreferrer"
           class="mt-3 flex items-center gap-2 rounded-xl bg-blue-50 ring-1 ring-blue-200 px-3.5 py-3 text-blue-800 hover:bg-blue-100 transition">
            <x-icono nombre="etiqueta" clase="w-5 h-5 shrink-0" />
            <span class="font-semibold text-sm">{{ __('Abrir ubicación en Google Maps') }}</span>
        </a>
    @endif

    @if ($this->puedeRegistrar())
        <form wire:submit="guardar" class="mt-4 space-y-4">
            <div>
                <x-input-label :value="__('Forma de pago')" />
                <div class="grid grid-cols-2 gap-2 mt-1">
                    @foreach ($metodos as $valor => $etiqueta)
                        <label class="cursor-pointer">
                            <input type="radio" wire:model="metodo_pago_gruero" value="{{ $valor }}" class="peer sr-only">
                            <span class="block text-center px-3 py-3 rounded-xl ring-1 ring-slate-200 bg-slate-50 text-slate-700 font-semibold text-sm
                                         peer-checked:bg-blue-700 peer-checked:text-white peer-checked:ring-blue-700 transition">
                                {{ $etiqueta }}
                            </span>
                        </label>
                    @endforeach
                </div>
                <x-input-error :messages="$errors->get('metodo_pago_gruero')" />
            </div>

            <div>
                <x-input-label for="ubicacion_destino" :value="__('¿Dónde dejaste el vehículo?')" />
                <select id="ubicacion_destino" wire:model="ubicacion_destino" class="campo w-full">
                    <option value="">{{ __('Seleccionar…') }}</option>
                    @foreach ($destinos as $valor => $etiqueta)
                        <option value="{{ $valor }}">{{ $etiqueta }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('ubicacion_destino')" />
            </div>

            <div>
                <x-input-label for="estado_titulo" :value="__('Titulación del auto')" />
                <select id="estado_titulo" wire:model="estado_titulo" class="campo w-full">
                    <option value="">{{ __('Seleccionar…') }}</option>
                    @foreach ($titulos as $valor => $etiqueta)
                        <option value="{{ $valor }}">{{ $etiqueta }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('estado_titulo')" />
            </div>

            <div>
                <x-input-label for="monto_pagado" :value="__('Monto pagado por el vehículo')" />
                <x-text-input id="monto_pagado" type="number" step="0.01" min="0" inputmode="decimal"
                              wire:model="monto_pagado" class="block w-full tabular" placeholder="0.00" />
                <p class="text-xs text-slate-400 mt-1">{{ __('Se registra como precio de compra, con la fecha del recojo.') }}</p>
                <x-input-error :messages="$errors->get('monto_pagado')" />
            </div>

            <div class="flex justify-end">
                <button type="submit" class="btn-primario" wire:loading.attr="disabled" wire:target="guardar">
                    <x-icono nombre="check" clase="w-5 h-5" />
                    <span wire:loading.remove wire:target="guardar">{{ __('Guardar recojo') }}</span>
                    <span wire:loading wire:target="guardar">{{ __('Guardando…') }}</span>
                </button>
            </div>
        </form>
    @else
        {{-- Solo lectura --}}
        <dl class="mt-4 grid grid-cols-2 gap-3 text-sm">
            <div>
                <dt class="text-slate-400 text-xs">{{ __('Forma de pago') }}</dt>
                <dd class="font-semibold text-slate-800">{{ $vehiculo->metodo_pago_gruero?->etiqueta() ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-slate-400 text-xs">{{ __('Dejado en') }}</dt>
                <dd class="font-semibold text-slate-800">{{ $vehiculo->ubicacion_destino?->etiqueta() ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-slate-400 text-xs">{{ __('Titulación') }}</dt>
                <dd>
                    @if ($vehiculo->estado_titulo)
                        <span class="chip {{ $vehiculo->estado_titulo->colorBadge() }}">{{ $vehiculo->estado_titulo->etiqueta() }}</span>
                    @else
                        <span class="text-slate-300">—</span>
                    @endif
                </dd>
            </div>

            @can('ver precios compra')
                <div>
                    <dt class="text-slate-400 text-xs">{{ __('Monto pagado') }}</dt>
                    <dd class="font-semibold text-slate-800 tabular">
                        {{ $vehiculo->monto_pagado !== null ? '$'.number_format((float) $vehiculo->monto_pagado, 2) : '—' }}
                    </dd>
                </div>
            @endcan
        </dl>
    @endif
</div>

<div class="py-6">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">

        <a href="{{ $vehiculo ? route('vehiculos.ficha', $vehiculo) : route('vehiculos.index') }}"
           class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 hover:text-slate-800 mb-3">
            <x-icono nombre="flecha-izq" clase="w-4 h-4" /> {{ __('← Volver a vehículos') }}
        </a>

        <div class="tarjeta p-5 sm:p-7">
            <h2 class="font-bold text-xl text-slate-800 mb-5 flex items-center gap-2">
                <x-icono nombre="vehiculo" clase="w-6 h-6 text-blue-600" />
                {{ $vehiculo ? __('Editar vehículo') : __('Nuevo vehículo') }}
            </h2>

            <form wire:submit="guardar" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <x-input-label for="marca" :value="__('Marca *')" />
                    <x-text-input id="marca" type="text" class="block w-full" wire:model="marca" placeholder="Toyota" />
                    <x-input-error :messages="$errors->get('marca')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="modelo" :value="__('Modelo *')" />
                    <x-text-input id="modelo" type="text" class="block w-full" wire:model="modelo" placeholder="Corolla" />
                    <x-input-error :messages="$errors->get('modelo')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="anio" :value="__('Año *')" />
                    <x-text-input id="anio" type="number" inputmode="numeric" class="block w-full" wire:model="anio" placeholder="2015" />
                    <x-input-error :messages="$errors->get('anio')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="vin" :value="__('VIN (17 caracteres) *')" />
                    <x-text-input id="vin" type="text" maxlength="17" class="block w-full uppercase font-mono" wire:model.blur="vin" placeholder="1HGBH41JXMN109186" />
                    <x-input-error :messages="$errors->get('vin')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="millas" :value="__('Millas *')" />
                    <x-text-input id="millas" type="number" inputmode="numeric" class="block w-full" wire:model="millas" placeholder="120000" />
                    <x-input-error :messages="$errors->get('millas')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="precio_compra" :value="__('Precio de compra (USD) *')" />
                    <x-text-input id="precio_compra" type="number" step="0.01" inputmode="decimal" class="block w-full" wire:model="precio_compra" placeholder="2500.00" />
                    <x-input-error :messages="$errors->get('precio_compra')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="fecha_compra" :value="__('Fecha de compra *')" />
                    <x-text-input id="fecha_compra" type="date" class="block w-full" wire:model="fecha_compra" />
                    <x-input-error :messages="$errors->get('fecha_compra')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="lugar_compra" :value="__('Lugar de compra *')" />
                    <select id="lugar_compra" wire:model="lugar_compra" class="campo">
                        <option value="">{{ __('— Seleccionar —') }}</option>
                        @foreach ($lugares as $valor => $etiqueta)
                            <option value="{{ $valor }}">{{ $etiqueta }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('lugar_compra')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="estado_titulo" :value="__('Estado del título *')" />
                    <select id="estado_titulo" wire:model="estado_titulo" class="campo">
                        <option value="">{{ __('— Seleccionar —') }}</option>
                        @foreach ($titulos as $valor => $etiqueta)
                            <option value="{{ $valor }}">{{ $etiqueta }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('estado_titulo')" class="mt-2" />
                </div>

                <div class="sm:col-span-2">
                    <x-input-label for="notas" :value="__('Notas')" />
                    <textarea id="notas" rows="3" wire:model="notas" class="campo"
                              placeholder="{{ __('Observaciones de la compra, daños, etc.') }}"></textarea>
                    <x-input-error :messages="$errors->get('notas')" class="mt-2" />
                </div>

                <div class="sm:col-span-2 flex flex-wrap gap-3 justify-end pt-2">
                    <a href="{{ $vehiculo ? route('vehiculos.ficha', $vehiculo) : route('vehiculos.index') }}" class="btn-secundario">
                        {{ __('Cancelar') }}
                    </a>
                    <button type="submit" class="btn-primario" wire:loading.attr="disabled">
                        <x-icono nombre="check" clase="w-5 h-5" />
                        <span wire:loading.remove wire:target="guardar">{{ $vehiculo ? __('Guardar cambios') : __('Registrar vehículo') }}</span>
                        <span wire:loading wire:target="guardar">{{ __('Guardando…') }}</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

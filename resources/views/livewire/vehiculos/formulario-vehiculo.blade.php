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
                    <x-input-label for="ubicacion_destino" :value="__('Dónde está *')" />
                    <select id="ubicacion_destino" wire:model="ubicacion_destino" class="campo">
                        <option value="">{{ __('— Seleccionar —') }}</option>
                        @foreach ($ubicaciones as $valor => $etiqueta)
                            <option value="{{ $valor }}">{{ $etiqueta }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('ubicacion_destino')" class="mt-2" />
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

                {{-- Foto del vehículo: la misma en alta y en edición --}}
                <div class="sm:col-span-2">
                    <x-input-label :value="__('Foto del vehículo')" />

                    @php
                        $urlPrevia = null;
                        if ($foto) {
                            try { $urlPrevia = $foto->temporaryUrl(); } catch (\Throwable) { $urlPrevia = null; }
                        }
                    @endphp

                    <div class="mt-1 flex flex-wrap items-start gap-4">
                        {{-- Vista de la foto: la nueva si acabás de elegirla, si no la guardada.
                             Si el archivo elegido no se puede previsualizar (un PDF, un .exe) igual
                             se muestra su nombre: nunca hay que quedarse sin saber qué se eligió. --}}
                        @if ($urlPrevia || $foto || $fotoActual)
                            <div class="relative w-40 h-28 rounded-xl overflow-hidden bg-slate-100 ring-1 ring-slate-200 shrink-0">
                                @if ($urlPrevia || (! $foto && $fotoActual))
                                    <img src="{{ $urlPrevia ?? $fotoActual->url() }}" alt="{{ __('Foto del vehículo') }}"
                                         class="w-full h-full object-cover">
                                @else
                                    <div class="grid place-items-center w-full h-full text-slate-400 px-2 text-center">
                                        <x-icono nombre="sin-foto" clase="w-7 h-7 mx-auto" />
                                        <span class="block text-[10px] mt-1 truncate w-full">{{ $foto->getClientOriginalName() }}</span>
                                    </div>
                                @endif

                                @if ($foto)
                                    <button type="button" wire:click="quitarFotoSeleccionada"
                                            class="absolute top-1 right-1 bg-slate-900/70 hover:bg-red-600 text-white rounded-full w-6 h-6 grid place-items-center text-xs font-bold"
                                            aria-label="{{ __('Quitar') }}">✕</button>
                                    <span class="absolute bottom-0 inset-x-0 bg-blue-700/90 text-white text-[10px] text-center py-0.5">{{ __('Nueva') }}</span>
                                @endif
                            </div>
                        @endif

                        {{-- Zona para elegir o arrastrar --}}
                        <div class="relative flex-1 min-w-[200px] rounded-2xl border-2 border-dashed border-slate-300 hover:border-blue-400 hover:bg-blue-50/40 transition p-5 text-center">
                            <input type="file" wire:model="foto" accept="image/*"
                                   class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                                   aria-label="{{ __('Elegir foto') }}">
                            <x-icono nombre="camara" clase="w-8 h-8 mx-auto text-slate-400" />
                            <p class="mt-2 text-sm font-semibold text-slate-700">
                                {{ $fotoActual ? __('Toca para cambiar la foto') : __('Toca para elegir una foto o arrástrala aquí') }}
                            </p>
                            <p class="text-xs text-slate-400 mt-0.5">{{ __('Una foto, máx. 10 MB') }}</p>
                            <p class="text-sm text-blue-700 font-medium mt-2" wire:loading wire:target="foto">{{ __('Cargando archivo…') }}</p>
                        </div>
                    </div>

                    <x-input-error :messages="$errors->get('foto')" class="mt-2" />
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

<div class="max-w-2xl mx-auto">
    <div class="flex items-center gap-3 mb-5">
        <a href="{{ route('vehiculos.index') }}" wire:navigate class="btn-secundario btn-sm">
            <x-icono nombre="flecha-izq" clase="w-4 h-4" />
        </a>
        <div>
            <h1 class="text-2xl font-bold text-slate-800">{{ __('Asignar recojo') }}</h1>
            <p class="text-sm text-slate-500">{{ __('El gruero verá únicamente los vehículos que le asignes.') }}</p>
        </div>
    </div>

    <form wire:submit="guardar" class="tarjeta p-4 sm:p-6 space-y-5">
        <div>
            <p class="etiqueta-seccion mb-3">{{ __('Datos del vehículo') }}</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <x-input-label for="marca" :value="__('Marca')" />
                    <x-text-input id="marca" wire:model="marca" class="block w-full" required />
                    <x-input-error :messages="$errors->get('marca')" />
                </div>
                <div>
                    <x-input-label for="modelo" :value="__('Modelo')" />
                    <x-text-input id="modelo" wire:model="modelo" class="block w-full" required />
                    <x-input-error :messages="$errors->get('modelo')" />
                </div>
                <div>
                    <x-input-label for="anio" :value="__('Año')" />
                    <x-text-input id="anio" type="number" inputmode="numeric" wire:model="anio" class="block w-full" required />
                    <x-input-error :messages="$errors->get('anio')" />
                </div>
                <div>
                    <x-input-label for="vin" :value="__('VIN (17 caracteres)')" />
                    <x-text-input id="vin" wire:model.blur="vin" class="block w-full uppercase font-mono"
                                  maxlength="17" autocapitalize="characters" autocomplete="off" required />
                    <x-input-error :messages="$errors->get('vin')" />
                </div>
            </div>
        </div>

        <div class="pt-4 border-t border-slate-100">
            <p class="etiqueta-seccion mb-3">{{ __('Ubicación del vehículo') }}</p>
            <x-input-label for="ubicacion_origen_url" :value="__('Enlace de Google Maps')" />
            <x-text-input id="ubicacion_origen_url" type="url" wire:model="ubicacion_origen_url"
                          class="block w-full" inputmode="url" autocomplete="off"
                          placeholder="https://maps.app.goo.gl/..." required />
            <p class="text-xs text-slate-400 mt-1">{{ __('Compartir ubicación desde Google Maps y pegar el enlace aquí.') }}</p>
            <x-input-error :messages="$errors->get('ubicacion_origen_url')" />
        </div>

        <div class="pt-4 border-t border-slate-100">
            <p class="etiqueta-seccion mb-3">{{ __('Gruero asignado') }}</p>
            @if ($grueros->isEmpty())
                <p class="text-sm text-amber-700 bg-amber-50 ring-1 ring-amber-200 rounded-xl p-3">
                    {{ __('No hay usuarios con rol Gruero. Creá uno desde Usuarios.') }}
                </p>
            @else
                <select id="asignado_a" wire:model="asignado_a" class="campo w-full" required>
                    <option value="">{{ __('Seleccionar gruero…') }}</option>
                    @foreach ($grueros as $gruero)
                        <option value="{{ $gruero->id }}">{{ $gruero->name }}</option>
                    @endforeach
                </select>
            @endif
            <x-input-error :messages="$errors->get('asignado_a')" />
        </div>

        <div class="flex justify-end gap-2 pt-2">
            <a href="{{ route('vehiculos.index') }}" wire:navigate class="btn-secundario">{{ __('Cancelar') }}</a>
            <button type="submit" class="btn-primario" wire:loading.attr="disabled" @disabled($grueros->isEmpty())>
                <x-icono nombre="check" clase="w-5 h-5" />
                <span wire:loading.remove wire:target="guardar">{{ __('Asignar recojo') }}</span>
                <span wire:loading wire:target="guardar">{{ __('Guardando…') }}</span>
            </button>
        </div>
    </form>
</div>

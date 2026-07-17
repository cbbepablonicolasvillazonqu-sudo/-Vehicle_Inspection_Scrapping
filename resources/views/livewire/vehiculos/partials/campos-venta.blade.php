{{-- Campos compartidos del formulario de venta (crear / editar) --}}
<div>
    <x-input-label for="fecha_venta" :value="__('Fecha de venta *')" />
    <x-text-input id="fecha_venta" type="date" class="mt-1 block w-full" wire:model="fecha_venta" />
    <x-input-error :messages="$errors->get('fecha_venta')" class="mt-2" />
</div>

<div>
    <x-input-label for="precio_venta" :value="__('Precio de venta (USD) *')" />
    <x-text-input id="precio_venta" type="number" step="0.01" inputmode="decimal" class="mt-1 block w-full" wire:model="precio_venta" placeholder="4500.00" />
    <x-input-error :messages="$errors->get('precio_venta')" class="mt-2" />
</div>

<div>
    <x-input-label for="nombre_comprador" :value="__('Nombre del comprador *')" />
    <x-text-input id="nombre_comprador" type="text" class="mt-1 block w-full" wire:model="nombre_comprador" />
    <x-input-error :messages="$errors->get('nombre_comprador')" class="mt-2" />
</div>

<div>
    <x-input-label for="telefono_comprador" :value="__('Teléfono del comprador *')" />
    <x-text-input id="telefono_comprador" type="tel" inputmode="tel" class="mt-1 block w-full" wire:model="telefono_comprador" placeholder="(555) 123-4567" />
    <x-input-error :messages="$errors->get('telefono_comprador')" class="mt-2" />
</div>

<div>
    <x-input-label for="metodo_pago" :value="__('Método de pago *')" />
    <select id="metodo_pago" wire:model="metodo_pago"
            class="mt-1 block w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm text-base">
        <option value="">{{ __('— Seleccionar —') }}</option>
        @foreach ($metodos as $valor => $etiqueta)
            <option value="{{ $valor }}">{{ $etiqueta }}</option>
        @endforeach
    </select>
    <x-input-error :messages="$errors->get('metodo_pago')" class="mt-2" />
</div>

<div class="sm:col-span-2">
    <x-input-label for="notas_venta" :value="__('Notas')" />
    <textarea id="notas_venta" rows="2" wire:model="notas"
              class="mt-1 block w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm text-base"></textarea>
    <x-input-error :messages="$errors->get('notas')" class="mt-2" />
</div>

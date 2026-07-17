{{-- Campos compartidos del formulario de desguace (crear / editar) --}}
<div>
    <x-input-label for="fecha_desguace" :value="__('Fecha *')" />
    <x-text-input id="fecha_desguace" type="date" class="mt-1 block w-full" wire:model="fecha" />
    <x-input-error :messages="$errors->get('fecha')" class="mt-2" />
</div>

<div>
    <x-input-label for="monto_recibido" :value="__('Monto recibido (USD) *')" />
    <x-text-input id="monto_recibido" type="number" step="0.01" inputmode="decimal" class="mt-1 block w-full" wire:model="monto_recibido" placeholder="350.00" />
    <x-input-error :messages="$errors->get('monto_recibido')" class="mt-2" />
</div>

<div class="sm:col-span-2">
    <x-input-label for="empresa" :value="__('Empresa / lugar de desguace *')" />
    <x-text-input id="empresa" type="text" class="mt-1 block w-full" wire:model="empresa" placeholder="Junkyard Central" />
    <x-input-error :messages="$errors->get('empresa')" class="mt-2" />
</div>

<div class="sm:col-span-2">
    <x-input-label for="notas_desguace" :value="__('Notas')" />
    <textarea id="notas_desguace" rows="2" wire:model="notas"
              class="mt-1 block w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm text-base"></textarea>
    <x-input-error :messages="$errors->get('notas')" class="mt-2" />
</div>

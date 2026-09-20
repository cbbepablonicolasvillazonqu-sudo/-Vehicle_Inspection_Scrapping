{{-- Campos compartidos del formulario de venta (crear / editar) --}}
<div>
    <x-input-label for="fecha_venta" :value="__('Fecha de venta')" />
    <x-text-input id="fecha_venta" type="date" class="mt-1 block w-full" wire:model="fecha_venta" />
    <x-input-error :messages="$errors->get('fecha_venta')" class="mt-2" />
</div>

<div>
    <x-input-label for="precio_venta" :value="__('Precio de venta (USD)')" />
    <x-text-input id="precio_venta" type="number" step="0.01" inputmode="decimal" class="mt-1 block w-full" wire:model="precio_venta" placeholder="4500.00" />
    <x-input-error :messages="$errors->get('precio_venta')" class="mt-2" />
</div>

<div>
    <x-input-label for="nombre_comprador" :value="__('Nombre del comprador')" />
    <x-text-input id="nombre_comprador" type="text" class="mt-1 block w-full" wire:model="nombre_comprador" />
    <x-input-error :messages="$errors->get('nombre_comprador')" class="mt-2" />
</div>

<div>
    <x-input-label for="telefono_comprador" :value="__('Teléfono del comprador')" />
    <x-text-input id="telefono_comprador" type="tel" inputmode="tel" class="mt-1 block w-full" wire:model="telefono_comprador" placeholder="(555) 123-4567" />
    <x-input-error :messages="$errors->get('telefono_comprador')" class="mt-2" />
</div>

<div>
    <x-input-label for="email_comprador" :value="__('Correo electrónico del comprador')" />
    <x-text-input id="email_comprador" type="email" inputmode="email" autocapitalize="none" autocorrect="off"
                  class="mt-1 block w-full" wire:model="email_comprador" placeholder="{{ __('cliente@correo.com') }}" />
    <x-input-error :messages="$errors->get('email_comprador')" class="mt-2" />
</div>

<div>
    <x-input-label for="metodo_pago" :value="__('Método de pago')" />
    <select id="metodo_pago" wire:model="metodo_pago"
            class="mt-1 block w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm text-base">
        <option value="">{{ __('— Seleccionar —') }}</option>
        @foreach ($metodos as $valor => $etiqueta)
            <option value="{{ $valor }}">{{ $etiqueta }}</option>
        @endforeach
    </select>
    <x-input-error :messages="$errors->get('metodo_pago')" class="mt-2" />
</div>

{{-- Contrato firmado: foto o PDF --}}
<div class="sm:col-span-2">
    <x-input-label :value="__('Foto del contrato (imagen o PDF)')" />

    @php
        $previaContrato = null;
        if ($contrato && ! str_ends_with(strtolower($contrato->getClientOriginalName()), '.pdf')) {
            try { $previaContrato = $contrato->temporaryUrl(); } catch (\Throwable) { $previaContrato = null; }
        }
        $contratoGuardado = $vehiculo->venta?->contratoUrl();
    @endphp

    <div class="mt-1 flex flex-wrap items-start gap-4">
        @if ($contrato || $contratoGuardado)
            <div class="relative w-32 h-28 rounded-xl overflow-hidden bg-slate-100 ring-1 ring-slate-200 shrink-0 grid place-items-center">
                @if ($previaContrato)
                    <img src="{{ $previaContrato }}" alt="{{ __('Contrato') }}" class="w-full h-full object-cover">
                @elseif ($contrato)
                    <div class="text-center text-slate-500 px-2">
                        <x-icono nombre="archivo" clase="w-8 h-8 mx-auto" />
                        <span class="block text-[10px] mt-1 truncate">{{ $contrato->getClientOriginalName() }}</span>
                    </div>
                @else
                    <a href="{{ $contratoGuardado }}" target="_blank" rel="noopener" class="text-center text-slate-500 px-2 hover:text-blue-700">
                        @if ($vehiculo->venta->contratoEsPdf())
                            <x-icono nombre="archivo" clase="w-8 h-8 mx-auto" />
                            <span class="block text-[10px] mt-1">{{ __('Ver contrato') }}</span>
                        @else
                            <img src="{{ $contratoGuardado }}" alt="{{ __('Contrato') }}" class="absolute inset-0 w-full h-full object-cover">
                        @endif
                    </a>
                @endif

                @if ($contrato)
                    <button type="button" wire:click="quitarContratoSeleccionado"
                            class="absolute top-1 right-1 bg-slate-900/70 hover:bg-red-600 text-white rounded-full w-6 h-6 grid place-items-center text-xs font-bold"
                            aria-label="{{ __('Quitar') }}">✕</button>
                @endif
            </div>
        @endif

        <div class="relative flex-1 min-w-[200px] rounded-2xl border-2 border-dashed border-slate-300 hover:border-blue-400 hover:bg-blue-50/40 transition p-5 text-center">
            <input type="file" wire:model="contrato" accept="image/*,application/pdf"
                   class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                   aria-label="{{ __('Elegir contrato') }}">
            <x-icono nombre="archivo" clase="w-8 h-8 mx-auto text-slate-400" />
            <p class="mt-2 text-sm font-semibold text-slate-700">
                {{ $contratoGuardado ? __('Toca para cambiar el contrato') : __('Toca para subir el contrato o arrástralo aquí') }}
            </p>
            <p class="text-xs text-slate-400 mt-0.5">{{ __('Foto o PDF, máx. 10 MB') }}</p>
            <p class="text-sm text-blue-700 font-medium mt-2" wire:loading wire:target="contrato">{{ __('Cargando archivo…') }}</p>
        </div>
    </div>

    <x-input-error :messages="$errors->get('contrato')" class="mt-2" />
</div>

<div class="sm:col-span-2">
    <x-input-label for="notas_venta" :value="__('Notas')" />
    <textarea id="notas_venta" rows="2" wire:model="notas"
              class="mt-1 block w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm text-base"></textarea>
    <x-input-error :messages="$errors->get('notas')" class="mt-2" />
</div>

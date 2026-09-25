<div class="tarjeta p-4 sm:p-6">
    <h3 class="text-lg font-bold text-slate-800 flex items-center gap-2">
        <x-icono nombre="engranaje" clase="w-5 h-5 text-slate-400" /> {{ __('Estado del vehículo') }}
    </h3>

    @php
        // "Publicado" se muestra como "Vendido" (pedido del cliente) y su botón
        // no cambia el estado: lleva al formulario de venta, y el estado pasa al
        // Vendido real recién al registrar la venta. Sin formulario en esta
        // pantalla no hay a dónde llevar, así que ese botón no se ofrece.
        $botones = collect($transiciones)->reject(
            fn ($destino) => $destino === \App\Enums\EstadoVehiculo::Publicado && ! $puedeVender
        );
    @endphp

    @if ($botones->isNotEmpty())
        <p class="mt-2 text-sm text-slate-500">{{ __('Mover a:') }}</p>

        <div class="mt-3 flex flex-wrap gap-3">
            @foreach ($botones as $destino)
                @if ($destino === \App\Enums\EstadoVehiculo::Publicado)
                    <button type="button" data-lleva-a="formulario-venta"
                            x-on:click="const f = document.getElementById('formulario-venta'); if (f) { f.scrollIntoView({ behavior: 'smooth', block: 'start' }); setTimeout(() => f.querySelector('input, select')?.focus({ preventScroll: true }), 450); }"
                            class="btn text-white shadow-sm px-5 py-3 {{ $destino->colorBoton() }}">
                        {{ $destino->etiqueta() }}
                    </button>
                @else
                    <button wire:click="cambiarEstado('{{ $destino->value }}')"
                            wire:confirm="{{ __('¿Cambiar el estado a «:estado»?', ['estado' => $destino->etiqueta()]) }}"
                            wire:loading.attr="disabled"
                            class="btn text-white shadow-sm px-5 py-3 {{ $destino->colorBoton() }}">
                        {{ $destino->etiqueta() }}
                    </button>
                @endif
            @endforeach
        </div>

        <div class="mt-3">
            <input type="text" wire:model="nota" maxlength="255"
                   placeholder="{{ __('Nota opcional del cambio (ej. «falta frenos»)…') }}"
                   class="campo">
            <x-input-error :messages="$errors->get('estado')" class="mt-2" />
        </div>
    @else
        <p class="mt-2 text-sm text-slate-500">
            @if ($vehiculo->estado->esFinal())
                {{ __('Estado final.') }} @role('admin') {{ __('Para revertirlo, elimina la venta o el registro de Junk car en su sección.') }} @endrole
            @else
                {{ __('Tu rol no tiene transiciones disponibles desde este estado.') }}
            @endif
        </p>
    @endif

    {{-- Historial de estados: línea de tiempo vertical --}}
    <div class="mt-6">
        <h4 class="etiqueta-seccion mb-3">{{ __('Historial de estados') }}</h4>

        <ol class="relative border-s-2 border-slate-100 ms-2 space-y-4">
            @forelse ($historial as $cambio)
                <li class="ms-5" wire:key="hist-{{ $cambio->id }}">
                    {{-- Punto en la línea --}}
                    <span class="absolute -start-[7px] mt-1 w-3 h-3 rounded-full ring-4 ring-white {{ $cambio->estado_nuevo->colorPunto() }}"></span>
                    <div class="text-sm">
                        <span class="font-semibold text-slate-800">
                            @if ($cambio->estado_anterior)
                                {{ $cambio->estado_anterior->etiquetaCorta() }} → {{ $cambio->estado_nuevo->etiquetaCorta() }}
                            @else
                                {{ $cambio->estado_nuevo->etiqueta() }}
                            @endif
                        </span>
                        <div class="text-xs text-slate-400 mt-0.5">
                            {{ $cambio->usuario?->name ?? __('Sistema') }} · {{ $cambio->created_at->format('d/m/Y H:i') }}
                        </div>
                        @if ($cambio->nota)
                            <div class="text-slate-500 italic mt-0.5">«{{ __($cambio->nota) }}»</div>
                        @endif
                    </div>
                </li>
            @empty
                <li class="ms-5 text-sm text-slate-500">{{ __('Sin cambios registrados.') }}</li>
            @endforelse
        </ol>
    </div>
</div>

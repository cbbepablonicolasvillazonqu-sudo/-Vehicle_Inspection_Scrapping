<div class="bg-white shadow rounded-xl p-4 sm:p-6">
    <h3 class="text-lg font-semibold text-gray-800">{{ __('Estado del vehículo') }}</h3>

    @if (count($transiciones))
        <p class="mt-1 text-sm text-gray-500">{{ __('Mover a:') }}</p>

        <div class="mt-3 flex flex-wrap gap-3">
            @foreach ($transiciones as $destino)
                <button wire:click="cambiarEstado('{{ $destino->value }}')"
                        wire:confirm="{{ __('¿Cambiar el estado a «:estado»?', ['estado' => $destino->etiqueta()]) }}"
                        wire:loading.attr="disabled"
                        class="px-5 py-3 rounded-xl text-white text-base font-semibold shadow {{ $destino->colorBoton() }}">
                    {{ $destino->etiqueta() }}
                </button>
            @endforeach
        </div>

        <div class="mt-3">
            <input type="text" wire:model="nota" maxlength="255"
                   placeholder="{{ __('Nota opcional del cambio (ej. «falta frenos»)…') }}"
                   class="w-full border-gray-300 rounded-xl text-base py-3 focus:border-blue-500 focus:ring-blue-500">
            <x-input-error :messages="$errors->get('estado')" class="mt-2" />
        </div>
    @else
        <p class="mt-1 text-sm text-gray-500">
            @if ($vehiculo->estado->esFinal())
                {{ __('Estado final.') }} @role('admin') {{ __('Para revertirlo, elimina la venta o el desguace en su sección.') }} @endrole
            @else
                {{ __('Tu rol no tiene transiciones disponibles desde este estado.') }}
            @endif
        </p>
    @endif

    {{-- Historial de estados: quién y cuándo --}}
    <div class="mt-5">
        <h4 class="text-sm font-semibold text-gray-600 uppercase tracking-wide">{{ __('Historial de estados') }}</h4>
        <ol class="mt-2 space-y-2">
            @forelse ($historial as $cambio)
                <li class="flex items-start gap-3 text-sm" wire:key="hist-{{ $cambio->id }}">
                    <span class="w-2.5 h-2.5 mt-1.5 rounded-full shrink-0 {{ $cambio->estado_nuevo->colorPunto() }}"></span>
                    <div>
                        <span class="font-medium text-gray-800">
                            @if ($cambio->estado_anterior)
                                {{ $cambio->estado_anterior->etiquetaCorta() }} → {{ $cambio->estado_nuevo->etiquetaCorta() }}
                            @else
                                {{ $cambio->estado_nuevo->etiqueta() }}
                            @endif
                        </span>
                        <span class="text-gray-500">· {{ $cambio->usuario?->name ?? __('Sistema') }}</span>
                        <span class="text-gray-400">· {{ $cambio->created_at->format('d/m/Y H:i') }}</span>
                        @if ($cambio->nota)
                            <div class="text-gray-500 italic">«{{ $cambio->nota }}»</div>
                        @endif
                    </div>
                </li>
            @empty
                <li class="text-sm text-gray-500">{{ __('Sin cambios registrados.') }}</li>
            @endforelse
        </ol>
    </div>
</div>

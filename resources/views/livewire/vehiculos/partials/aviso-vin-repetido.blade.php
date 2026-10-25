{{-- Aviso de VIN repetido. Avisa, no bloquea: el mismo auto puede volver a
     entrar, pero casi siempre es un error de tipeo o el auto cargado dos veces.
     Recibe $repetido = Vehicle::conMismoVin(...). Solo se nombran los
     vehículos que el usuario puede ver; de los demás, cuántos son.
     Los enlaces abren otra pestaña para no perder lo que se está cargando. --}}
@if ($repetido['visibles']->isNotEmpty() || $repetido['ocultos'] > 0)
    <div class="w-full rounded-xl bg-amber-50 ring-1 ring-amber-200 px-3.5 py-2.5 text-sm text-amber-900 {{ $clase ?? 'mt-2' }}" role="status">
        <p class="font-semibold">⚠ {{ __('Este VIN ya está en otro vehículo:') }}</p>

        @if ($repetido['visibles']->isNotEmpty())
            <ul class="mt-1 space-y-0.5">
                @foreach ($repetido['visibles'] as $otro)
                    <li wire:key="vin-repetido-{{ $otro->id }}">
                        <a href="{{ route('vehiculos.ficha', $otro) }}" target="_blank" rel="noopener"
                           class="font-semibold underline hover:text-amber-700">{{ $otro->nombreCompleto() }}</a>
                        <span class="text-amber-700">· {{ $otro->estado->etiquetaCorta() }}</span>
                    </li>
                @endforeach
            </ul>
        @endif

        @if ($repetido['ocultos'] > 0)
            <p class="mt-1">{{ trans_choice('{1} También está en :n vehículo que no podés ver.|[2,*] También está en :n vehículos que no podés ver.', $repetido['ocultos'], ['n' => $repetido['ocultos']]) }}</p>
        @endif

        <p class="mt-1 text-amber-800">{{ __('No impide guardar. Revisá que no sea un error de tipeo o el mismo auto cargado dos veces.') }}</p>
    </div>
@endif

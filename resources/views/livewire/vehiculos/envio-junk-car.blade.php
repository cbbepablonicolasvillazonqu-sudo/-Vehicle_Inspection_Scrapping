<div class="max-w-4xl mx-auto">
    <div class="flex items-center gap-3 mb-5">
        <a href="{{ route('vehiculos.index') }}" wire:navigate class="btn-secundario btn-sm">
            <x-icono nombre="flecha-izq" clase="w-4 h-4" />
        </a>
        <div>
            <h1 class="text-2xl font-bold text-slate-800">{{ __('Enviar a Junk car') }}</h1>
            <p class="text-sm text-slate-500">{{ __('Buscá, filtrá y seleccioná varios vehículos a la vez.') }}</p>
        </div>
    </div>

    {{-- Buscador y filtros --}}
    <div class="tarjeta p-4 mb-4">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div class="sm:col-span-2 relative">
                <x-icono nombre="buscar" clase="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
                <input type="search" wire:model.live.debounce.250ms="buscar" class="campo w-full ps-10 pe-10"
                       placeholder="{{ __('Buscar por marca, modelo, VIN o año…') }}">
                <span wire:loading wire:target="buscar"
                      class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-medium text-blue-700">
                    {{ __('Buscando…') }}
                </span>
            </div>
            <select wire:model.live="estado" class="campo w-full">
                <option value="">{{ __('Todos los estados') }}</option>
                @foreach ($estados as $valor => $etiqueta)
                    <option value="{{ $valor }}">{{ $etiqueta }}</option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- Barra de selección --}}
    @if (count($seleccion))
        <div class="sticky top-16 z-20 mb-4 flex flex-wrap items-center justify-between gap-3 rounded-2xl bg-slate-800 text-white px-4 py-3 shadow-lg">
            <div class="text-sm">
                <span class="font-semibold">
                    {{ trans_choice('{1} :n vehículo seleccionado|[2,*] :n vehículos seleccionados', count($seleccion), ['n' => count($seleccion)]) }}
                </span>
                {{-- Enviar a Junk car no se puede deshacer: si hay
                     seleccionados que el filtro oculta, hay que decirlo. --}}
                @if ($fueraDelFiltro > 0)
                    <span class="block mt-0.5 text-amber-300 font-medium">
                        ⚠ {{ trans_choice('{1} :n no se ve con el filtro actual y también se enviará|[2,*] :n no se ven con el filtro actual y también se enviarán', $fueraDelFiltro, ['n' => $fueraDelFiltro]) }}
                    </span>
                @endif
            </div>
            <div class="flex gap-2">
                <button type="button" wire:click="limpiarSeleccion" class="btn-sm bg-slate-600 hover:bg-slate-500 text-white rounded-xl px-3 py-2">
                    {{ __('Quitar selección') }}
                </button>
                <button type="button" wire:click="enviar"
                        wire:confirm="{{ trans_choice('{1} ¿Enviar :n vehículo a Junk car? Quedará bloqueado y no se puede deshacer.|[2,*] ¿Enviar :n vehículos a Junk car? Quedarán bloqueados y no se puede deshacer.', count($seleccion), ['n' => count($seleccion)]) }}"
                        class="btn-sm bg-slate-200 hover:bg-white text-slate-900 font-bold rounded-xl px-3 py-2"
                        wire:loading.attr="disabled" wire:target="enviar">
                    <span wire:loading.remove wire:target="enviar">{{ __('Enviar a Junk car') }}</span>
                    <span wire:loading wire:target="enviar">{{ __('Enviando…') }}</span>
                </button>
            </div>
        </div>
    @endif

    {{-- Resultados --}}
    <div class="tarjeta divide-y divide-slate-100">
        @if ($vehiculos->count())
            <label class="flex items-center gap-3 px-4 py-3 bg-slate-50 rounded-t-2xl cursor-pointer">
                <input type="checkbox" class="rounded border-slate-300 text-blue-700 focus:ring-blue-500"
                       wire:click="alternarPagina({{ json_encode($idsPagina) }})"
                       @checked(count(array_diff($idsPagina, $seleccion)) === 0)>
                <span class="text-sm font-semibold text-slate-600">{{ __('Seleccionar todos los de esta página') }}</span>
            </label>
        @endif

        @forelse ($vehiculos as $vehiculo)
            <label class="flex items-center gap-3 px-4 py-3.5 hover:bg-slate-50 cursor-pointer" wire:key="junk-{{ $vehiculo->id }}">
                <input type="checkbox" value="{{ $vehiculo->id }}" wire:model.live="seleccion"
                       class="rounded border-slate-300 text-blue-700 focus:ring-blue-500">
                <div class="min-w-0 flex-1">
                    <p class="font-semibold text-slate-800 truncate">{{ $vehiculo->nombreCompleto() }}</p>
                    <p class="text-xs text-slate-500 font-mono truncate">{{ $vehiculo->vin }}</p>
                </div>
                <span class="chip {{ $vehiculo->estado->colorBadge() }}">{{ $vehiculo->estado->etiquetaCorta() }}</span>
            </label>
        @empty
            <div class="py-12 text-center">
                <x-icono nombre="buscar" clase="w-10 h-10 mx-auto text-slate-300" />
                <p class="mt-2 text-sm text-slate-500">{{ __('No hay vehículos que coincidan.') }}</p>
            </div>
        @endforelse
    </div>

    @if ($vehiculos->hasPages())
        <div class="mt-4">{{ $vehiculos->links() }}</div>
    @endif
</div>

<div class="tarjeta p-4 sm:p-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h3 class="text-lg font-bold text-slate-800 flex items-center gap-2">
            <x-icono nombre="dinero" clase="w-5 h-5 text-slate-400" /> {{ __('Gastos') }}
            <span class="ms-1 text-sm font-bold text-slate-700 bg-slate-100 px-3 py-1 rounded-full tabular">
                {{ __('Total: :monto', ['monto' => dinero($total)]) }}
            </span>
        </h3>

        @if ($this->puedeRegistrar() && ! $mostrandoFormulario)
            <button wire:click="nuevo" class="btn-primario btn-sm">
                <x-icono nombre="mas" clase="w-5 h-5" /> {{ __('Agregar gasto') }}
            </button>
        @endif
    </div>

    {{-- Formulario --}}
    @if ($mostrandoFormulario)
        <form wire:submit="guardar" class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4 bg-slate-50 border border-slate-200 rounded-xl p-4">
            <div>
                <x-input-label for="categoria" :value="__('Categoría *')" />
                <select id="categoria" wire:model="categoria" class="campo">
                    <option value="">{{ __('— Seleccionar —') }}</option>
                    @foreach ($categorias as $valor => $etiqueta)
                        <option value="{{ $valor }}">{{ $etiqueta }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('categoria')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="monto" :value="__('Monto (USD) *')" />
                <x-text-input id="monto" type="number" step="0.01" inputmode="decimal" class="block w-full" wire:model="monto" placeholder="150.00" />
                <x-input-error :messages="$errors->get('monto')" class="mt-2" />
            </div>

            <div class="sm:col-span-2">
                <x-input-label for="descripcion" :value="__('Descripción *')" />
                <x-text-input id="descripcion" type="text" class="block w-full" wire:model="descripcion" placeholder="{{ __('Cambio de pastillas de freno') }}" />
                <x-input-error :messages="$errors->get('descripcion')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="fecha" :value="__('Fecha *')" />
                <x-text-input id="fecha" type="date" class="block w-full" wire:model="fecha" />
                <x-input-error :messages="$errors->get('fecha')" class="mt-2" />
            </div>

            <div class="sm:col-span-2 flex gap-3 justify-end">
                <button type="button" wire:click="cancelar" class="btn-secundario btn-sm">{{ __('Cancelar') }}</button>
                <button type="submit" class="btn-primario btn-sm">{{ $gastoId ? __('Guardar cambios') : __('Registrar gasto') }}</button>
            </div>
        </form>
    @endif

    {{-- Lista de gastos --}}
    <div class="mt-4 divide-y divide-slate-100">
        @forelse ($gastos as $gasto)
            <div class="py-3 flex flex-wrap items-center justify-between gap-2" wire:key="gasto-{{ $gasto->id }}">
                <div class="flex items-center gap-3 min-w-0">
                    <span class="grid place-items-center w-9 h-9 rounded-xl shrink-0 {{ $gasto->categoria->colorChip() }}">
                        <x-icono :nombre="$gasto->categoria->icono()" clase="w-5 h-5" />
                    </span>
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="chip {{ $gasto->categoria->colorChip() }}">{{ $gasto->categoria->etiqueta() }}</span>
                            <span class="font-medium text-slate-900 truncate">{{ $gasto->descripcion }}</span>
                        </div>
                        <div class="text-xs text-slate-400 mt-0.5">
                            {{ $gasto->fecha->format('d/m/Y') }} · {{ $gasto->usuario?->name ?? '—' }}
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-1">
                    <span class="font-bold text-slate-900 text-lg tabular">{{ dinero($gasto->monto) }}</span>

                    @if (auth()->user()->hasRole('admin') || ($gasto->user_id === auth()->id() && $this->puedeRegistrar()))
                        <button wire:click="editar({{ $gasto->id }})" class="p-2 text-blue-700 hover:bg-blue-50 rounded-lg" aria-label="{{ __('Editar') }}">
                            <x-icono nombre="editar" clase="w-4 h-4" />
                        </button>
                        <button wire:click="eliminar({{ $gasto->id }})"
                                wire:confirm="{{ __('¿Eliminar este gasto de :monto?', ['monto' => dinero($gasto->monto)]) }}"
                                class="p-2 text-red-600 hover:bg-red-50 rounded-lg" aria-label="{{ __('Eliminar') }}">
                            <x-icono nombre="basura" clase="w-4 h-4" />
                        </button>
                    @endif
                </div>
            </div>
        @empty
            <p class="py-3 text-sm text-slate-500">{{ __('Sin gastos registrados.') }}</p>
        @endforelse
    </div>
</div>

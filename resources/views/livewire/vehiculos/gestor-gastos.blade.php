<div class="bg-white shadow rounded-xl p-4 sm:p-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h3 class="text-lg font-semibold text-gray-800">
            {{ __('Gastos') }}
            <span class="ms-2 text-base font-bold text-gray-900 bg-gray-100 px-3 py-1 rounded-full">
                {{ __('Total: :monto', ['monto' => dinero($total)]) }}
            </span>
        </h3>

        @if ($this->puedeRegistrar() && ! $mostrandoFormulario)
            <button wire:click="nuevo"
                    class="px-4 py-3 bg-blue-700 hover:bg-blue-800 text-white text-base font-semibold rounded-xl shadow">
                {{ __('+ Agregar gasto') }}
            </button>
        @endif
    </div>

    {{-- Formulario --}}
    @if ($mostrandoFormulario)
        <form wire:submit="guardar" class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4 bg-gray-50 border border-gray-200 rounded-xl p-4">
            <div>
                <x-input-label for="categoria" :value="__('Categoría *')" />
                <select id="categoria" wire:model="categoria"
                        class="mt-1 block w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm text-base">
                    <option value="">{{ __('— Seleccionar —') }}</option>
                    @foreach ($categorias as $valor => $etiqueta)
                        <option value="{{ $valor }}">{{ $etiqueta }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('categoria')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="monto" :value="__('Monto (USD) *')" />
                <x-text-input id="monto" type="number" step="0.01" inputmode="decimal" class="mt-1 block w-full" wire:model="monto" placeholder="150.00" />
                <x-input-error :messages="$errors->get('monto')" class="mt-2" />
            </div>

            <div class="sm:col-span-2">
                <x-input-label for="descripcion" :value="__('Descripción *')" />
                <x-text-input id="descripcion" type="text" class="mt-1 block w-full" wire:model="descripcion" placeholder="{{ __('Cambio de pastillas de freno') }}" />
                <x-input-error :messages="$errors->get('descripcion')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="fecha" :value="__('Fecha *')" />
                <x-text-input id="fecha" type="date" class="mt-1 block w-full" wire:model="fecha" />
                <x-input-error :messages="$errors->get('fecha')" class="mt-2" />
            </div>

            <div class="sm:col-span-2 flex gap-3 justify-end">
                <button type="button" wire:click="cancelar"
                        class="px-4 py-3 bg-white border border-gray-300 rounded-xl text-base font-medium text-gray-700 hover:bg-gray-50">
                    {{ __('Cancelar') }}
                </button>
                <button type="submit"
                        class="px-5 py-3 bg-blue-700 hover:bg-blue-800 text-white text-base font-semibold rounded-xl shadow">
                    {{ $gastoId ? __('Guardar cambios') : __('Registrar gasto') }}
                </button>
            </div>
        </form>
    @endif

    {{-- Lista de gastos --}}
    <div class="mt-4 divide-y divide-gray-100">
        @forelse ($gastos as $gasto)
            <div class="py-3 flex flex-wrap items-center justify-between gap-2" wire:key="gasto-{{ $gasto->id }}">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-700">
                            {{ $gasto->categoria->etiqueta() }}
                        </span>
                        <span class="font-medium text-gray-900">{{ $gasto->descripcion }}</span>
                    </div>
                    <div class="text-xs text-gray-500 mt-1">
                        {{ $gasto->fecha->format('d/m/Y') }} · {{ $gasto->usuario?->name ?? '—' }}
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <span class="font-bold text-gray-900 text-lg">{{ dinero($gasto->monto) }}</span>

                    @if (auth()->user()->hasRole('admin') || ($gasto->user_id === auth()->id() && $this->puedeRegistrar()))
                        <button wire:click="editar({{ $gasto->id }})"
                                class="px-3 py-2 text-sm font-medium text-blue-700 hover:bg-blue-50 rounded-lg">
                            {{ __('Editar') }}
                        </button>
                        <button wire:click="eliminar({{ $gasto->id }})"
                                wire:confirm="{{ __('¿Eliminar este gasto de :monto?', ['monto' => dinero($gasto->monto)]) }}"
                                class="px-3 py-2 text-sm font-medium text-red-600 hover:bg-red-50 rounded-lg">
                            {{ __('Eliminar') }}
                        </button>
                    @endif
                </div>
            </div>
        @empty
            <p class="py-3 text-sm text-gray-500">{{ __('Sin gastos registrados.') }}</p>
        @endforelse
    </div>
</div>

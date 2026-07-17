<div class="py-6">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800">{{ __('Usuarios del equipo') }}</h2>
            <button wire:click="crear"
                    class="inline-flex items-center px-4 py-3 bg-blue-700 hover:bg-blue-800 text-white text-base font-semibold rounded-xl shadow">
                {{ __('+ Nuevo usuario') }}
            </button>
        </div>

        @php $etiquetasRol = ['admin' => __('Administrador'), 'comprador' => __('Comprador'), 'mecanico' => __('Mecánico'), 'vendedor' => __('Vendedor')]; @endphp

        {{-- Formulario crear / editar --}}
        @if ($mostrandoFormulario)
            <div class="bg-white shadow rounded-xl p-4 sm:p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">
                    {{ $usuarioId ? __('Editar usuario') : __('Nuevo usuario') }}
                </h3>

                <form wire:submit="guardar" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="name" :value="__('Nombre')" />
                        <x-text-input id="name" type="text" class="mt-1 block w-full" wire:model="name" />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="email" :value="__('Correo electrónico')" />
                        <x-text-input id="email" type="email" class="mt-1 block w-full" wire:model="email"
                                      autocapitalize="none" autocorrect="off" spellcheck="false" inputmode="email" />
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="password" :value="$usuarioId ? __('Contraseña nueva (opcional)') : __('Contraseña')" />
                        <x-text-input id="password" type="password" class="mt-1 block w-full" wire:model="password" autocomplete="new-password" />
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="rol" :value="__('Rol')" />
                        <select id="rol" wire:model="rol"
                                class="mt-1 block w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm text-base">
                            <option value="">{{ __('— Seleccionar rol —') }}</option>
                            @foreach ($roles as $nombreRol)
                                <option value="{{ $nombreRol }}">{{ $etiquetasRol[$nombreRol] ?? ucfirst($nombreRol) }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('rol')" class="mt-2" />
                    </div>

                    <div class="sm:col-span-2 flex gap-3 justify-end pt-2">
                        <button type="button" wire:click="cancelar"
                                class="px-4 py-3 bg-white border border-gray-300 rounded-xl text-base font-medium text-gray-700 hover:bg-gray-50">
                            {{ __('Cancelar') }}
                        </button>
                        <button type="submit"
                                class="px-5 py-3 bg-blue-700 hover:bg-blue-800 text-white text-base font-semibold rounded-xl shadow">
                            {{ __('Guardar') }}
                        </button>
                    </div>
                </form>
            </div>
        @endif

        {{-- Lista de usuarios --}}
        <div class="bg-white shadow rounded-xl divide-y divide-gray-100">
            @foreach ($usuarios as $usuario)
                <div class="p-4 flex flex-wrap items-center gap-3 justify-between">
                    <div class="min-w-0">
                        <div class="font-semibold text-gray-900 flex items-center gap-2">
                            {{ $usuario->name }}
                            @if ($usuario->id === auth()->id())
                                <span class="text-xs text-gray-400">{{ __('(tú)') }}</span>
                            @endif
                        </div>
                        <div class="text-sm text-gray-500 truncate">{{ $usuario->email }}</div>
                    </div>

                    <div class="flex items-center gap-2">
                        <span class="px-3 py-1 rounded-full text-sm font-medium
                            @switch($usuario->getRoleNames()->first())
                                @case('admin') bg-purple-100 text-purple-800 @break
                                @case('comprador') bg-orange-100 text-orange-800 @break
                                @case('mecanico') bg-yellow-100 text-yellow-800 @break
                                @case('vendedor') bg-blue-100 text-blue-800 @break
                                @default bg-gray-100 text-gray-600
                            @endswitch">
                            {{ $usuario->nombreRol() }}
                        </span>

                        <button wire:click="editar({{ $usuario->id }})"
                                class="px-3 py-2 text-sm font-medium text-blue-700 hover:bg-blue-50 rounded-lg">
                            {{ __('Editar') }}
                        </button>

                        @if ($usuario->id !== auth()->id())
                            <button wire:click="eliminar({{ $usuario->id }})"
                                    wire:confirm="{{ __('¿Eliminar al usuario :nombre?', ['nombre' => $usuario->name]) }}"
                                    class="px-3 py-2 text-sm font-medium text-red-600 hover:bg-red-50 rounded-lg">
                                {{ __('Eliminar') }}
                            </button>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

    </div>
</div>

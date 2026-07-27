<div class="py-6">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">

        <div class="flex items-center justify-between">
            <h2 class="font-bold text-lg text-slate-800 flex items-center gap-2">
                <x-icono nombre="usuarios" clase="w-6 h-6 text-blue-600" /> {{ __('Usuarios del equipo') }}
            </h2>
            <button wire:click="crear" class="btn-primario btn-sm">
                <x-icono nombre="mas" clase="w-5 h-5" /> {{ __('Nuevo usuario') }}
            </button>
        </div>

        @php
            $etiquetasRol = ['admin' => __('Administrador'), 'gruero' => __('Gruero'), 'mecanico' => __('Mecánico'), 'vendedor' => __('Vendedor')];
            $coloresRol = [
                'admin' => ['chip' => 'bg-purple-100 text-purple-800', 'av' => 'from-purple-500 to-purple-700'],
                'gruero' => ['chip' => 'bg-orange-100 text-orange-800', 'av' => 'from-orange-500 to-orange-700'],
                'mecanico' => ['chip' => 'bg-yellow-100 text-yellow-800', 'av' => 'from-yellow-500 to-amber-600'],
                'vendedor' => ['chip' => 'bg-blue-100 text-blue-800', 'av' => 'from-blue-500 to-blue-700'],
            ];
        @endphp

        {{-- Formulario crear / editar --}}
        @if ($mostrandoFormulario)
            <div class="tarjeta p-4 sm:p-6">
                <h3 class="text-base font-bold text-slate-800 mb-4">
                    {{ $usuarioId ? __('Editar usuario') : __('Nuevo usuario') }}
                </h3>

                <form wire:submit="guardar" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="name" :value="__('Nombre')" />
                        <x-text-input id="name" type="text" class="block w-full" wire:model="name" />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="email" :value="__('Correo electrónico')" />
                        <x-text-input id="email" type="email" class="block w-full" wire:model="email"
                                      autocapitalize="none" autocorrect="off" spellcheck="false" inputmode="email" />
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="password" :value="$usuarioId ? __('Contraseña nueva (opcional)') : __('Contraseña')" />
                        <x-text-input id="password" type="password" class="block w-full" wire:model="password" autocomplete="new-password" />
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="rol" :value="__('Rol')" />
                        <select id="rol" wire:model="rol" class="campo">
                            <option value="">{{ __('— Seleccionar rol —') }}</option>
                            @foreach ($roles as $nombreRol)
                                <option value="{{ $nombreRol }}">{{ $etiquetasRol[$nombreRol] ?? ucfirst($nombreRol) }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('rol')" class="mt-2" />
                    </div>

                    <div class="sm:col-span-2 flex gap-3 justify-end pt-2">
                        <button type="button" wire:click="cancelar" class="btn-secundario btn-sm">{{ __('Cancelar') }}</button>
                        <button type="submit" class="btn-primario btn-sm">{{ __('Guardar') }}</button>
                    </div>
                </form>
            </div>
        @endif

        {{-- Lista de usuarios --}}
        <div class="tarjeta divide-y divide-slate-100">
            @foreach ($usuarios as $usuario)
                @php
                    $rol = $usuario->getRoleNames()->first();
                    $c = $coloresRol[$rol] ?? ['chip' => 'bg-slate-100 text-slate-600', 'av' => 'from-slate-500 to-slate-700'];
                @endphp
                <div class="p-4 flex flex-wrap items-center gap-3 justify-between">
                    <div class="flex items-center gap-3 min-w-0">
                        <span class="grid place-items-center w-11 h-11 rounded-full bg-gradient-to-br {{ $c['av'] }} text-white font-bold shrink-0">
                            {{ mb_substr($usuario->name, 0, 1) }}
                        </span>
                        <div class="min-w-0">
                            <div class="font-semibold text-slate-900 flex items-center gap-2">
                                {{ $usuario->name }}
                                @if ($usuario->id === auth()->id())
                                    <span class="text-xs text-slate-400">{{ __('(tú)') }}</span>
                                @endif
                            </div>
                            <div class="text-sm text-slate-400 truncate">{{ $usuario->email }}</div>
                        </div>
                    </div>

                    <div class="flex items-center gap-1.5">
                        <span class="chip {{ $c['chip'] }}">{{ $usuario->nombreRol() }}</span>

                        <button wire:click="editar({{ $usuario->id }})" class="p-2 text-blue-700 hover:bg-blue-50 rounded-lg" aria-label="{{ __('Editar') }}">
                            <x-icono nombre="editar" clase="w-4 h-4" />
                        </button>

                        @if ($usuario->id !== auth()->id())
                            <button wire:click="eliminar({{ $usuario->id }})"
                                    wire:confirm="{{ __('¿Eliminar al usuario :nombre?', ['nombre' => $usuario->name]) }}"
                                    class="p-2 text-red-600 hover:bg-red-50 rounded-lg" aria-label="{{ __('Eliminar') }}">
                                <x-icono nombre="basura" clase="w-4 h-4" />
                            </button>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

    </div>
</div>

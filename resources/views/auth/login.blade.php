<x-guest-layout>
    <h2 class="text-lg font-bold text-slate-800 mb-1">{{ __('Iniciar sesión') }}</h2>
    <p class="text-sm text-slate-500 mb-5">{{ __('Accede a tu cuenta para gestionar el inventario.') }}</p>

    <!-- Estado de la sesión -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" x-data="{ email: '{{ old('email') }}', password: '' }">
        @csrf

        <!-- Correo electrónico -->
        <div>
            <x-input-label for="email" :value="__('Correo electrónico')" />
            <x-text-input id="email" class="block w-full" type="email" name="email"
                          x-model="email"
                          required autofocus autocomplete="username"
                          autocapitalize="none" autocorrect="off" spellcheck="false" inputmode="email" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Contraseña -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Contraseña')" />
            <x-text-input id="password" class="block w-full"
                            type="password" name="password" x-model="password"
                            required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Recordarme -->
        <div class="flex items-center justify-between mt-4">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded border-slate-300 text-blue-600 shadow-sm focus:ring-blue-500" name="remember">
                <span class="ms-2 text-sm text-slate-600">{{ __('Recordarme') }}</span>
            </label>

            @if (Route::has('password.request'))
                <a class="text-sm text-blue-700 hover:text-blue-900 hover:underline" href="{{ route('password.request') }}">
                    {{ __('¿Olvidaste tu contraseña?') }}
                </a>
            @endif
        </div>

        <x-primary-button class="w-full mt-6">
            {{ __('Iniciar sesión') }}
        </x-primary-button>

        {{-- Acceso rápido de demostración: SOLO en entorno local (no aparece en producción).
             Rellena el formulario con un toque para evitar errores de tipeo en el celular. --}}
        @if (app()->environment('local'))
            <div class="mt-6 pt-5 border-t border-slate-200">
                <p class="etiqueta-seccion mb-2.5">{{ __('Cuentas de demostración (toca una)') }}</p>
                <div class="grid grid-cols-2 gap-2">
                    @foreach ([
                        ['Admin', 'admin@fortetowing.com', 'bg-purple-50 text-purple-800 hover:bg-purple-100 ring-purple-200'],
                        ['Comprador', 'compras@fortetowing.com', 'bg-orange-50 text-orange-800 hover:bg-orange-100 ring-orange-200'],
                        ['Mecánico', 'taller@fortetowing.com', 'bg-yellow-50 text-yellow-800 hover:bg-yellow-100 ring-yellow-200'],
                        ['Vendedor', 'ventas@fortetowing.com', 'bg-blue-50 text-blue-800 hover:bg-blue-100 ring-blue-200'],
                    ] as [$rol, $correo, $clase])
                        <button type="button"
                                @click="email = '{{ $correo }}'; password = 'password'"
                                class="px-3 py-2.5 rounded-xl ring-1 text-sm font-semibold text-left transition {{ $clase }}">
                            {{ __($rol) }}
                            <span class="block text-[11px] font-normal opacity-70 truncate">{{ $correo }}</span>
                        </button>
                    @endforeach
                </div>
                <p class="text-[11px] text-slate-400 mt-2.5">{{ __('Contraseña de todas:') }} <span class="font-mono font-semibold">password</span></p>
            </div>
        @endif
    </form>
</x-guest-layout>

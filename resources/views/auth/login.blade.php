<x-guest-layout>
    <!-- Estado de la sesión -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" x-data="{ email: '{{ old('email') }}', password: '' }">
        @csrf

        <!-- Correo electrónico -->
        <div>
            <x-input-label for="email" :value="__('Correo electrónico')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email"
                          x-model="email"
                          required autofocus autocomplete="username"
                          autocapitalize="none" autocorrect="off" spellcheck="false" inputmode="email" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Contraseña -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Contraseña')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            x-model="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Recordarme -->
        <div class="block mt-4">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500" name="remember">
                <span class="ms-2 text-sm text-gray-600">{{ __('Recordarme') }}</span>
            </label>
        </div>

        <div class="flex items-center justify-between mt-6">
            @if (Route::has('password.request'))
                <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500" href="{{ route('password.request') }}">
                    {{ __('¿Olvidaste tu contraseña?') }}
                </a>
            @endif

            <x-primary-button class="ms-3">
                {{ __('Iniciar sesión') }}
            </x-primary-button>
        </div>

        {{-- Acceso rápido de demostración: SOLO en entorno local (no aparece en producción).
             Rellena el formulario con un toque para evitar errores de tipeo en el celular. --}}
        @if (app()->environment('local'))
            <div class="mt-6 pt-5 border-t border-gray-200">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">
                    {{ __('Cuentas de demostración (toca una)') }}
                </p>
                <div class="grid grid-cols-2 gap-2">
                    @foreach ([
                        ['Admin', 'admin@fortetowing.com'],
                        ['Comprador', 'compras@fortetowing.com'],
                        ['Mecánico', 'taller@fortetowing.com'],
                        ['Vendedor', 'ventas@fortetowing.com'],
                    ] as [$rol, $correo])
                        <button type="button"
                                @click="email = '{{ $correo }}'; password = 'password'"
                                class="px-3 py-2.5 bg-gray-100 hover:bg-blue-100 text-gray-700 text-sm font-medium rounded-lg text-left">
                            {{ __($rol) }}
                            <span class="block text-[11px] text-gray-400 truncate">{{ $correo }}</span>
                        </button>
                    @endforeach
                </div>
                <p class="text-[11px] text-gray-400 mt-2">{{ __('Contraseña de todas:') }} <span class="font-mono">password</span></p>
            </div>
        @endif
    </form>
</x-guest-layout>

<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
    <!-- Menú principal -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-2">
                        <x-application-logo class="block h-9 w-auto text-blue-800" />
                        <span class="hidden md:block font-bold text-gray-800">Forte Towing</span>
                    </a>
                </div>

                <!-- Enlaces de navegación (escritorio) -->
                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        {{ __('Panel') }}
                    </x-nav-link>

                    @if (Route::has('vehiculos.index'))
                        @can('ver vehiculos')
                            <x-nav-link :href="route('vehiculos.index')" :active="request()->routeIs('vehiculos.*')">
                                {{ __('Vehículos') }}
                            </x-nav-link>
                        @endcan
                    @endif

                    @if (Route::has('reportes.ganancias'))
                        @can('ver ganancias')
                            <x-nav-link :href="route('reportes.ganancias')" :active="request()->routeIs('reportes.*')">
                                {{ __('Reportes') }}
                            </x-nav-link>
                        @endcan
                    @endif

                    @if (Route::has('usuarios.index'))
                        @can('gestionar usuarios')
                            <x-nav-link :href="route('usuarios.index')" :active="request()->routeIs('usuarios.*')">
                                {{ __('Usuarios') }}
                            </x-nav-link>
                        @endcan
                    @endif
                </div>
            </div>

            <!-- Idioma + menú de usuario (escritorio) -->
            <div class="hidden sm:flex sm:items-center sm:ms-6 gap-3">
                <x-selector-idioma />

                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                            <div class="text-left">
                                <div>{{ Auth::user()->name }}</div>
                                <div class="text-xs text-gray-400">{{ Auth::user()->nombreRol() }}</div>
                            </div>

                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Mi perfil') }}
                        </x-dropdown-link>

                        <!-- Cerrar sesión -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                {{ __('Cerrar sesión') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Botón hamburguesa (móvil) -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-3 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                    <svg class="h-7 w-7" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Menú responsive (móvil) -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                {{ __('Panel') }}
            </x-responsive-nav-link>

            @if (Route::has('vehiculos.index'))
                @can('ver vehiculos')
                    <x-responsive-nav-link :href="route('vehiculos.index')" :active="request()->routeIs('vehiculos.*')">
                        {{ __('Vehículos') }}
                    </x-responsive-nav-link>
                @endcan
            @endif

            @if (Route::has('reportes.ganancias'))
                @can('ver ganancias')
                    <x-responsive-nav-link :href="route('reportes.ganancias')" :active="request()->routeIs('reportes.*')">
                        {{ __('Reportes') }}
                    </x-responsive-nav-link>
                @endcan
            @endif

            @if (Route::has('usuarios.index'))
                @can('gestionar usuarios')
                    <x-responsive-nav-link :href="route('usuarios.index')" :active="request()->routeIs('usuarios.*')">
                        {{ __('Usuarios') }}
                    </x-responsive-nav-link>
                @endcan
            @endif
        </div>

        <!-- Opciones de usuario (móvil) -->
        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-gray-500">{{ Auth::user()->nombreRol() }} · {{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 px-4">
                <span class="text-sm text-gray-500">{{ __('Idioma') }}:</span>
                <x-selector-idioma class="ms-2 align-middle" />
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Mi perfil') }}
                </x-responsive-nav-link>

                <!-- Cerrar sesión -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('Cerrar sesión') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>

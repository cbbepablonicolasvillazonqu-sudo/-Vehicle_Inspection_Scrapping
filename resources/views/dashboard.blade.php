<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Panel principal
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm rounded-xl">
                <div class="p-6 text-gray-900">
                    ¡Bienvenido, {{ Auth::user()->name }}! Sesión iniciada como
                    <span class="font-semibold">{{ Auth::user()->nombreRol() }}</span>.
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

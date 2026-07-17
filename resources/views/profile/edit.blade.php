<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-lg text-slate-800 leading-tight flex items-center gap-2">
            <x-icono nombre="usuario" clase="w-6 h-6 text-blue-600" /> {{ __('Mi perfil') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">
            <div class="tarjeta p-5 sm:p-7">
                <div class="max-w-xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <div class="tarjeta p-5 sm:p-7">
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            <div class="tarjeta p-5 sm:p-7">
                <div class="max-w-xl">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">

        <h2 class="font-semibold text-xl text-gray-800">
            {{ __('Hola') }}, {{ auth()->user()->name }} 👋
            <span class="block sm:inline text-sm font-normal text-gray-500">{{ auth()->user()->nombreRol() }} · Forte Towing</span>
        </h2>

        {{-- Tarjetas por estado --}}
        <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-3">
            <a href="{{ route('vehiculos.index') }}" class="bg-white shadow rounded-xl p-4 hover:ring-2 hover:ring-blue-200">
                <div class="text-3xl font-extrabold text-gray-900">{{ $conteos['inventario'] }}</div>
                <div class="text-sm text-gray-500 mt-1">{{ __('En inventario') }}</div>
            </a>

            <a href="{{ route('vehiculos.index', ['estado' => 'en_reparacion']) }}" class="bg-white shadow rounded-xl p-4 border-s-4 border-yellow-400 hover:ring-2 hover:ring-yellow-200">
                <div class="text-3xl font-extrabold text-yellow-600">{{ $conteos['reparacion'] }}</div>
                <div class="text-sm text-gray-500 mt-1">{{ __('En reparación') }}</div>
            </a>

            <a href="{{ route('vehiculos.index', ['estado' => 'listo']) }}" class="bg-white shadow rounded-xl p-4 border-s-4 border-green-500 hover:ring-2 hover:ring-green-200">
                <div class="text-3xl font-extrabold text-green-600">{{ $conteos['listos'] }}</div>
                <div class="text-sm text-gray-500 mt-1">{{ __('Listos') }}</div>
            </a>

            <a href="{{ route('vehiculos.index', ['estado' => 'publicado']) }}" class="bg-white shadow rounded-xl p-4 border-s-4 border-sky-500 hover:ring-2 hover:ring-sky-200">
                <div class="text-3xl font-extrabold text-sky-600">{{ $conteos['publicados'] }}</div>
                <div class="text-sm text-gray-500 mt-1">{{ __('Publicados') }}</div>
            </a>

            <a href="{{ route('vehiculos.index', ['estado' => 'vendido']) }}" class="bg-white shadow rounded-xl p-4 border-s-4 border-blue-600 hover:ring-2 hover:ring-blue-200">
                <div class="text-3xl font-extrabold text-blue-700">{{ $conteos['vendidosMes'] }}</div>
                <div class="text-sm text-gray-500 mt-1">{{ __('Vendidos este mes') }}</div>
            </a>

            <a href="{{ route('vehiculos.index', ['estado' => 'desguace']) }}" class="bg-white shadow rounded-xl p-4 border-s-4 border-gray-500 hover:ring-2 hover:ring-gray-300">
                <div class="text-3xl font-extrabold text-gray-600">{{ $conteos['desguace'] }}</div>
                <div class="text-sm text-gray-500 mt-1">{{ __('Desguace') }}</div>
            </a>
        </div>

        {{-- Finanzas (solo Admin) --}}
        @if ($finanzas)
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div class="bg-gray-900 text-white shadow rounded-xl p-4">
                    <div class="text-sm text-gray-300">{{ __('Total invertido en inventario actual') }}</div>
                    <div class="text-2xl font-extrabold mt-1">{{ dinero($finanzas['invertido']) }}</div>
                    <div class="text-xs text-gray-400 mt-1">{{ __('compra + gastos de vehículos activos') }}</div>
                </div>
                <div class="bg-white shadow rounded-xl p-4 border-s-4 {{ $finanzas['gananciaMes'] >= 0 ? 'border-green-500' : 'border-red-500' }}">
                    <div class="text-sm text-gray-500">{{ __('Ganancia de :mes', ['mes' => now()->translatedFormat('F Y')]) }}</div>
                    <div class="text-2xl font-extrabold mt-1 {{ $finanzas['gananciaMes'] >= 0 ? 'text-green-700' : 'text-red-700' }}">
                        {{ dinero($finanzas['gananciaMes']) }}
                    </div>
                    <a href="{{ route('reportes.ganancias') }}" class="text-xs text-blue-700 hover:underline mt-1 inline-block">{{ __('Ver reporte →') }}</a>
                </div>
                <div class="bg-white shadow rounded-xl p-4 border-s-4 {{ $finanzas['gananciaAcumulada'] >= 0 ? 'border-green-500' : 'border-red-500' }}">
                    <div class="text-sm text-gray-500">{{ __('Ganancia acumulada') }}</div>
                    <div class="text-2xl font-extrabold mt-1 {{ $finanzas['gananciaAcumulada'] >= 0 ? 'text-green-700' : 'text-red-700' }}">
                        {{ dinero($finanzas['gananciaAcumulada']) }}
                    </div>
                    <div class="text-xs text-gray-400 mt-1">{{ __('ventas + desguaces históricos') }}</div>
                </div>
            </div>
        @endif

        {{-- Lista de vehículos con búsqueda y filtro --}}
        <livewire:vehiculos.lista-vehiculos />
    </div>
</div>

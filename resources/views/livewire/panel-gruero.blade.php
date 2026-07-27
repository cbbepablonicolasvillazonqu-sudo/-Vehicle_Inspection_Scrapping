<div class="py-6">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

        <div class="flex items-center gap-3">
            <span class="grid place-items-center w-11 h-11 rounded-full bg-gradient-to-br from-orange-500 to-orange-700 text-white text-lg font-bold shrink-0">
                {{ mb_substr(auth()->user()->name, 0, 1) }}
            </span>
            <div>
                <h2 class="font-bold text-lg text-slate-800 leading-tight">{{ __('Hola') }}, {{ auth()->user()->name }} 👋</h2>
                <p class="text-sm text-slate-500">{{ auth()->user()->nombreRol() }} · Forte Towing</p>
            </div>
        </div>

        {{-- Resumen rápido --}}
        <div class="grid grid-cols-2 gap-3">
            @php
                $resumen = [
                    ['n' => $porRecoger->count(), 'txt' => __('Por recoger'), 'icono' => 'vehiculo', 'chip' => 'bg-orange-100 text-orange-700', 'num' => 'text-orange-600'],
                    ['n' => $junkPorCompletar->count(), 'txt' => __('Junk car por completar'), 'icono' => 'engranaje', 'chip' => 'bg-amber-100 text-amber-700', 'num' => 'text-amber-600'],
                ];
            @endphp
            @foreach ($resumen as $r)
                <div class="tarjeta p-4">
                    <div class="flex items-center justify-between">
                        <span class="grid place-items-center w-9 h-9 rounded-xl {{ $r['chip'] }}">
                            <x-icono :nombre="$r['icono']" clase="w-5 h-5" />
                        </span>
                        <span class="text-3xl font-extrabold tabular {{ $r['num'] }}">{{ $r['n'] }}</span>
                    </div>
                    <div class="text-sm text-slate-500 mt-2">{{ $r['txt'] }}</div>
                </div>
            @endforeach
        </div>

        {{-- 1. Por recoger --}}
        <section>
            <div class="flex items-center gap-2 mb-2.5">
                <span class="w-2.5 h-2.5 rounded-full bg-orange-500"></span>
                <h3 class="font-bold text-slate-800">{{ __('Por recoger') }}</h3>
                <span class="chip bg-slate-100 text-slate-600">{{ $porRecoger->count() }}</span>
            </div>

            <div class="tarjeta divide-y divide-slate-100">
                @forelse ($porRecoger as $vehiculo)
                    <div class="p-4 flex flex-wrap items-center gap-3" wire:key="pr-{{ $vehiculo->id }}">
                        <div class="min-w-0 flex-1">
                            <a href="{{ route('vehiculos.ficha', $vehiculo) }}" wire:navigate
                               class="font-semibold text-slate-900 hover:text-blue-700 block truncate">
                                {{ $vehiculo->nombreCompleto() }}
                            </a>
                            <p class="text-xs text-slate-500 font-mono truncate">{{ $vehiculo->vin }}</p>
                        </div>

                        <div class="flex gap-2">
                            @if ($vehiculo->ubicacion_origen_url)
                                <a href="{{ $vehiculo->ubicacion_origen_url }}" target="_blank" rel="noopener noreferrer"
                                   class="btn-secundario btn-sm" title="{{ __('Abrir ubicación en Google Maps') }}">
                                    <x-icono nombre="etiqueta" clase="w-4 h-4" /> {{ __('Ubicación') }}
                                </a>
                            @endif
                            <a href="{{ route('vehiculos.ficha', $vehiculo) }}" wire:navigate class="btn-primario btn-sm">
                                {{ __('Registrar recojo') }}
                            </a>
                        </div>
                    </div>
                @empty
                    <p class="p-5 text-sm text-slate-500 text-center">{{ __('No tenés vehículos pendientes de recoger.') }}</p>
                @endforelse
            </div>
        </section>

        {{-- 2. Recogidos --}}
        <section>
            <div class="flex flex-wrap items-center gap-2 mb-2.5">
                <span class="w-2.5 h-2.5 rounded-full bg-green-500"></span>
                <h3 class="font-bold text-slate-800">{{ __('Recogidos (últimos :n días)', ['n' => $recogidosDias]) }}</h3>
                <span class="chip bg-slate-100 text-slate-600">{{ $recogidosTotal }}</span>
                <a href="{{ route('vehiculos.index') }}" wire:navigate
                   class="ms-auto text-sm font-semibold text-blue-700 hover:underline">
                    {{ __('Ver todo mi historial') }}
                </a>
            </div>

            <div class="tarjeta divide-y divide-slate-100">
                @forelse ($recogidos as $vehiculo)
                    <a href="{{ route('vehiculos.ficha', $vehiculo) }}" wire:navigate
                       class="p-4 flex flex-wrap items-center gap-3 hover:bg-slate-50" wire:key="rec-{{ $vehiculo->id }}">
                        <div class="min-w-0 flex-1">
                            <span class="font-semibold text-slate-900 block truncate">{{ $vehiculo->nombreCompleto() }}</span>
                            <p class="text-xs text-slate-500 font-mono truncate">{{ $vehiculo->vin }}</p>
                        </div>
                        <div class="flex flex-wrap items-center gap-1.5">
                            @if ($vehiculo->ubicacion_destino)
                                <span class="chip {{ $vehiculo->ubicacion_destino->colorBadge() }}">{{ $vehiculo->ubicacion_destino->etiqueta() }}</span>
                            @endif
                            @if ($vehiculo->estado_titulo)
                                <span class="chip {{ $vehiculo->estado_titulo->colorBadge() }}">{{ $vehiculo->estado_titulo->etiqueta() }}</span>
                            @endif
                        </div>
                    </a>
                @empty
                    <p class="p-5 text-sm text-slate-500 text-center">{{ __('Todavía no registraste ningún recojo.') }}</p>
                @endforelse

                @if ($recogidosTotal > $recogidos->count())
                    <a href="{{ route('vehiculos.index') }}" wire:navigate
                       class="block p-3 text-center text-sm font-semibold text-blue-700 hover:bg-slate-50">
                        {{ __('y :n más', ['n' => $recogidosTotal - $recogidos->count()]) }}
                    </a>
                @endif
            </div>
        </section>

        {{-- 3. Junk car por completar --}}
        <section>
            <div class="flex items-center gap-2 mb-2.5">
                <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                <h3 class="font-bold text-slate-800">{{ __('Junk car por completar') }}</h3>
                <span class="chip bg-slate-100 text-slate-600">{{ $junkPorCompletar->count() }}</span>
            </div>

            <div class="tarjeta divide-y divide-slate-100">
                @forelse ($junkPorCompletar as $vehiculo)
                    <div class="p-4 flex flex-wrap items-center gap-3" wire:key="jp-{{ $vehiculo->id }}">
                        <div class="min-w-0 flex-1">
                            <a href="{{ route('vehiculos.ficha', $vehiculo) }}" wire:navigate
                               class="font-semibold text-slate-900 hover:text-blue-700 block truncate">
                                {{ $vehiculo->nombreCompleto() }}
                            </a>
                            <p class="text-xs text-amber-700">
                                {{ $vehiculo->tiene_catalizador === null ? __('Falta catalizador y monto') : __('Falta el monto pagado') }}
                            </p>
                        </div>
                        <a href="{{ route('vehiculos.ficha', $vehiculo) }}" wire:navigate class="btn-primario btn-sm">
                            {{ __('Completar') }}
                        </a>
                    </div>
                @empty
                    <p class="p-5 text-sm text-slate-500 text-center">{{ __('No hay Junk car pendientes.') }}</p>
                @endforelse
            </div>
        </section>

        {{-- 4. Junk car completados --}}
        @if ($junkCompletados->isNotEmpty())
            <section>
                <div class="flex items-center gap-2 mb-2.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-slate-400"></span>
                    <h3 class="font-bold text-slate-800">{{ __('Junk car completados') }}</h3>
                    <span class="chip bg-slate-100 text-slate-600">{{ $junkCompletados->count() }}</span>
                </div>

                <div class="tarjeta divide-y divide-slate-100">
                    @foreach ($junkCompletados as $vehiculo)
                        <a href="{{ route('vehiculos.ficha', $vehiculo) }}" wire:navigate
                           class="p-4 flex flex-wrap items-center gap-3 hover:bg-slate-50" wire:key="jc-{{ $vehiculo->id }}">
                            <div class="min-w-0 flex-1">
                                <span class="font-semibold text-slate-900 block truncate">{{ $vehiculo->nombreCompleto() }}</span>
                                <p class="text-xs text-slate-500">
                                    {{ $vehiculo->tiene_catalizador ? __('Con catalizador') : __('Sin catalizador') }}
                                </p>
                            </div>
                            <x-icono nombre="check" clase="w-5 h-5 text-green-600" />
                        </a>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</div>

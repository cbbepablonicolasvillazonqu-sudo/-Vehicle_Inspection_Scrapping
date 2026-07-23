<div class="tarjeta p-4 sm:p-6">
    <h3 class="text-lg font-bold text-slate-800 flex items-center gap-2">
        <x-icono nombre="camara" clase="w-5 h-5 text-slate-400" /> {{ __('Fotos') }}
    </h3>

    {{-- Pestañas por etapa --}}
    <div class="mt-3 flex gap-2 flex-wrap">
        @foreach ($etapas as $opcion)
            <button type="button"
                    wire:click="$set('etapa', '{{ $opcion->value }}')"
                    class="px-4 py-2 rounded-full text-sm font-semibold transition
                        {{ $etapa === $opcion->value
                            ? 'bg-blue-700 text-white shadow-sm'
                            : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                {{ $opcion->etiqueta() }}
                @php $n = ($fotosPorEtapa[$opcion->value] ?? collect())->count(); @endphp
                @if ($n) <span class="opacity-75">· {{ $n }}</span> @endif
            </button>
        @endforeach
    </div>

    {{-- Apartado de subida con previsualización --}}
    @if ($this->puedeGestionar())
        @php $etiquetaEtapa = \App\Enums\EtapaFoto::from($etapa)->etiqueta(); @endphp

        <div class="mt-4">
            {{-- Zona para elegir / arrastrar (el input invisible cubre toda el área) --}}
            <div class="relative rounded-2xl border-2 border-dashed border-slate-300 hover:border-blue-400 hover:bg-blue-50/40 transition p-6 text-center">
                <input type="file" wire:model="fotos" multiple accept="image/*"
                       class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                       aria-label="{{ __('Elegir fotos') }}">
                <x-icono nombre="camara" clase="w-9 h-9 mx-auto text-slate-400" />
                <p class="mt-2 text-sm font-semibold text-slate-700">{{ __('Toca para elegir fotos o arrástralas aquí') }}</p>
                <p class="text-xs text-slate-400 mt-0.5">{{ __('Se subirán a la etapa «:etapa» · máx. 10 fotos, 10 MB c/u', ['etapa' => $etiquetaEtapa]) }}</p>
                <p class="text-sm text-blue-700 font-medium mt-2" wire:loading wire:target="fotos">{{ __('Cargando archivos…') }}</p>
            </div>

            <x-input-error :messages="$errors->get('fotos')" class="mt-2" />
            <x-input-error :messages="$errors->get('fotos.*')" class="mt-2" />

            {{-- Previsualización de la selección antes de confirmar --}}
            @if (count($fotos))
                <div class="mt-3 bg-slate-50 border border-slate-200 rounded-2xl p-3.5">
                    <p class="etiqueta-seccion mb-2.5">{{ __('Previsualización (:n)', ['n' => count($fotos)]) }}</p>

                    <div class="grid grid-cols-4 sm:grid-cols-6 gap-2">
                        @foreach ($fotos as $indice => $foto)
                            @php
                                try { $urlPrevia = $foto->temporaryUrl(); } catch (\Throwable) { $urlPrevia = null; }
                            @endphp
                            <div class="relative rounded-xl overflow-hidden aspect-square bg-slate-200 ring-1 ring-slate-200"
                                 wire:key="previa-{{ $indice }}-{{ $foto->getFilename() }}">
                                @if ($urlPrevia)
                                    <img src="{{ $urlPrevia }}" class="w-full h-full object-cover" alt="">
                                @else
                                    <div class="grid place-items-center w-full h-full text-slate-400">
                                        <x-icono nombre="sin-foto" clase="w-6 h-6" />
                                    </div>
                                @endif
                                <button type="button" wire:click="quitarSeleccion({{ $indice }})"
                                        class="absolute top-1 right-1 bg-slate-900/70 hover:bg-red-600 text-white rounded-full w-6 h-6 grid place-items-center text-xs font-bold"
                                        aria-label="{{ __('Quitar') }}">✕</button>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-3 flex flex-wrap gap-2 justify-end">
                        <button type="button" wire:click="limpiarSeleccion" class="btn-secundario btn-sm">
                            {{ __('Cancelar') }}
                        </button>
                        <button type="button" wire:click="subir" class="btn-primario btn-sm"
                                wire:loading.attr="disabled" wire:target="subir,fotos">
                            <x-icono nombre="check" clase="w-4 h-4" />
                            <span wire:loading.remove wire:target="subir">{{ __('Subir :n a «:etapa»', ['n' => count($fotos), 'etapa' => $etiquetaEtapa]) }}</span>
                            <span wire:loading wire:target="subir">{{ __('Subiendo…') }}</span>
                        </button>
                    </div>
                </div>
            @endif
        </div>
    @endif

    {{-- Galería de la etapa activa --}}
    @php $fotosEtapa = $fotosPorEtapa[$etapa] ?? collect(); @endphp

    <div class="mt-4 grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
        @forelse ($fotosEtapa as $foto)
            <div class="relative group rounded-xl overflow-hidden bg-slate-100 aspect-square ring-1 ring-slate-200" wire:key="foto-{{ $foto->id }}">
                <a href="{{ $foto->url() }}" target="_blank" rel="noopener">
                    <img src="{{ $foto->url() }}" alt="{{ $foto->nombre_original ?? __('Foto del vehículo') }}"
                         loading="lazy" class="w-full h-full object-cover transition group-hover:scale-105">
                </a>

                @if (auth()->user()->hasRole('admin') || ($foto->user_id === auth()->id() && $this->puedeGestionar()))
                    <button wire:click="eliminarFoto({{ $foto->id }})"
                            wire:confirm="{{ __('¿Eliminar esta foto?') }}"
                            class="absolute top-1.5 right-1.5 bg-slate-900/60 hover:bg-red-600 text-white rounded-full w-8 h-8 grid place-items-center opacity-0 group-hover:opacity-100 transition" aria-label="{{ __('Eliminar') }}">
                        <x-icono nombre="basura" clase="w-4 h-4" />
                    </button>
                @endif

                <div class="absolute bottom-0 inset-x-0 bg-gradient-to-t from-black/60 to-transparent text-white text-[10px] px-2 py-1.5 truncate">
                    {{ $foto->usuario?->name ?? '—' }} · {{ $foto->created_at->format('d/m/y') }}
                </div>
            </div>
        @empty
            <div class="col-span-full py-8 text-center">
                <x-icono nombre="sin-foto" clase="w-10 h-10 mx-auto text-slate-300" />
                <p class="mt-2 text-sm text-slate-500">{{ __('Sin fotos en la etapa «:etapa».', ['etapa' => \App\Enums\EtapaFoto::from($etapa)->etiqueta()]) }}</p>
            </div>
        @endforelse
    </div>
</div>

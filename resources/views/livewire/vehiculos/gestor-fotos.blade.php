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

    {{-- Subida --}}
    @if ($this->puedeGestionar())
        <form wire:submit="subir" class="mt-4 flex flex-wrap items-center gap-3">
            <input type="file" wire:model="fotos" multiple accept="image/*" capture="environment"
                   class="block text-sm text-slate-600 file:me-3 file:px-4 file:py-2.5 file:rounded-xl file:border-0 file:bg-blue-50 file:text-blue-700 file:font-semibold hover:file:bg-blue-100 file:cursor-pointer">

            <button type="submit" class="btn-primario btn-sm"
                    wire:loading.attr="disabled" wire:target="fotos,subir">
                <x-icono nombre="descargar" clase="w-4 h-4 rotate-180" />
                <span wire:loading.remove wire:target="subir">{{ __('Subir a «:etapa»', ['etapa' => \App\Enums\EtapaFoto::from($etapa)->etiqueta()]) }}</span>
                <span wire:loading wire:target="subir">{{ __('Subiendo…') }}</span>
            </button>

            <span class="text-sm text-slate-400" wire:loading wire:target="fotos">{{ __('Cargando archivos…') }}</span>
        </form>
        <x-input-error :messages="$errors->get('fotos')" class="mt-2" />
        <x-input-error :messages="$errors->get('fotos.*')" class="mt-2" />
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

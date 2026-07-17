<div class="bg-white shadow rounded-xl p-4 sm:p-6">
    <h3 class="text-lg font-semibold text-gray-800">Fotos</h3>

    {{-- Pestañas por etapa --}}
    <div class="mt-3 flex gap-2">
        @foreach ($etapas as $opcion)
            <button type="button"
                    wire:click="$set('etapa', '{{ $opcion->value }}')"
                    class="px-4 py-2.5 rounded-xl text-sm font-semibold transition
                        {{ $etapa === $opcion->value
                            ? 'bg-blue-700 text-white shadow'
                            : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                {{ $opcion->etiqueta() }}
                @php $n = ($fotosPorEtapa[$opcion->value] ?? collect())->count(); @endphp
                @if ($n) <span class="opacity-75">({{ $n }})</span> @endif
            </button>
        @endforeach
    </div>

    {{-- Subida --}}
    @if ($this->puedeGestionar())
        <form wire:submit="subir" class="mt-4 flex flex-wrap items-center gap-3">
            <input type="file" wire:model="fotos" multiple accept="image/*" capture="environment"
                   class="block text-sm text-gray-600 file:me-3 file:px-4 file:py-3 file:rounded-xl file:border-0 file:bg-blue-50 file:text-blue-700 file:font-semibold hover:file:bg-blue-100">

            <button type="submit"
                    class="px-5 py-3 bg-blue-700 hover:bg-blue-800 text-white text-base font-semibold rounded-xl shadow disabled:opacity-50"
                    wire:loading.attr="disabled" wire:target="fotos,subir">
                <span wire:loading.remove wire:target="subir">Subir a «{{ \App\Enums\EtapaFoto::from($etapa)->etiqueta() }}»</span>
                <span wire:loading wire:target="subir">Subiendo…</span>
            </button>

            <span class="text-sm text-gray-400" wire:loading wire:target="fotos">Cargando archivos…</span>
        </form>
        <x-input-error :messages="$errors->get('fotos')" class="mt-2" />
        <x-input-error :messages="$errors->get('fotos.*')" class="mt-2" />
    @endif

    {{-- Galería de la etapa activa --}}
    @php $fotosEtapa = $fotosPorEtapa[$etapa] ?? collect(); @endphp

    <div class="mt-4 grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
        @forelse ($fotosEtapa as $foto)
            <div class="relative group rounded-xl overflow-hidden bg-gray-100 aspect-square" wire:key="foto-{{ $foto->id }}">
                <a href="{{ $foto->url() }}" target="_blank" rel="noopener">
                    <img src="{{ $foto->url() }}" alt="{{ $foto->nombre_original ?? 'Foto del vehículo' }}"
                         loading="lazy" class="w-full h-full object-cover">
                </a>

                @if (auth()->user()->hasRole('admin') || ($foto->user_id === auth()->id() && $this->puedeGestionar()))
                    <button wire:click="eliminarFoto({{ $foto->id }})"
                            wire:confirm="¿Eliminar esta foto?"
                            class="absolute top-1.5 right-1.5 bg-black/60 hover:bg-red-600 text-white rounded-full w-8 h-8 text-sm font-bold">
                        ✕
                    </button>
                @endif

                <div class="absolute bottom-0 inset-x-0 bg-black/45 text-white text-[10px] px-2 py-1 truncate">
                    {{ $foto->usuario?->name ?? '—' }} · {{ $foto->created_at->format('d/m/y') }}
                </div>
            </div>
        @empty
            <p class="col-span-full text-sm text-gray-500 py-3">Sin fotos en la etapa «{{ \App\Enums\EtapaFoto::from($etapa)->etiqueta() }}».</p>
        @endforelse
    </div>
</div>

<div class="tarjeta p-4 sm:p-6">
    <h3 class="text-lg font-bold text-slate-800 flex items-center gap-2">
        <x-icono nombre="camara" clase="w-5 h-5 text-slate-400" /> {{ __('Fotos, VIN y documentos') }}
    </h3>

    {{-- VIN: lo puede corregir cualquiera que vea la ficha; en un registro
         bloqueado, solo el Admin. El encabezado se actualiza solo. --}}
    @if ($this->puedeModificar())
        <form wire:submit="guardarVin" class="mt-4 flex flex-wrap items-end gap-3">
            <div class="flex-1 min-w-[200px]">
                <x-input-label for="vin-ficha" :value="__('VIN (17 caracteres)')" />
                <x-text-input id="vin-ficha" type="text" maxlength="17" class="block w-full uppercase font-mono"
                              wire:model="vin" placeholder="1HGBH41JXMN109186" />
            </div>
            <button type="submit" class="btn-primario btn-sm" wire:loading.attr="disabled" wire:target="guardarVin">
                <x-icono nombre="check" clase="w-4 h-4" /> {{ __('Guardar VIN') }}
            </button>
            <x-input-error :messages="$errors->get('vin')" class="w-full" />
        </form>
    @endif

    {{-- Fotos del vehículo. Dos botones a propósito: "Tomar foto" abre la
         cámara trasera directo (capture); el otro, la galería. --}}
    <div class="mt-6">
        <h4 class="etiqueta-seccion mb-3">{{ __('Fotos del vehículo') }}</h4>

        <div class="rounded-2xl border-2 border-dashed border-slate-300 p-5 text-center">
            <div class="flex flex-wrap justify-center gap-2">
                <label class="btn-primario btn-sm cursor-pointer">
                    <input type="file" wire:model="nuevasFotos" accept="image/*" capture="environment" multiple class="sr-only">
                    <x-icono nombre="camara" clase="w-4 h-4" /> {{ __('Tomar foto') }}
                </label>
                <label class="btn-secundario btn-sm cursor-pointer">
                    <input type="file" wire:model="nuevasFotos" accept="image/*" multiple class="sr-only">
                    <x-icono nombre="mas" clase="w-4 h-4" /> {{ __('Elegir de la galería') }}
                </label>
            </div>
            <p class="text-xs text-slate-400 mt-2">{{ __('Máx. 5 fotos, 10 MB c/u') }}</p>
            <p class="text-sm text-blue-700 font-medium mt-2" wire:loading wire:target="nuevasFotos">{{ __('Cargando archivos…') }}</p>
        </div>

        <x-input-error :messages="$errors->get('nuevasFotos')" class="mt-2" />
        <x-input-error :messages="$errors->get('nuevasFotos.*')" class="mt-2" />

        @if ($galeria->isNotEmpty())
            <div class="mt-3 grid grid-cols-4 sm:grid-cols-6 md:grid-cols-8 gap-2">
                @foreach ($galeria as $foto)
                    <div class="relative rounded-xl overflow-hidden aspect-square bg-slate-100 ring-1 ring-slate-200"
                         wire:key="galeria-{{ $foto->id }}">
                        <a href="{{ $foto->url() }}" target="_blank" rel="noopener">
                            <img src="{{ $foto->url() }}" alt="{{ $foto->nombre_original ?? __('Foto del vehículo') }}"
                                 loading="lazy" class="w-full h-full object-cover">
                        </a>

                        @if ($foto->id === $portadaId)
                            <span class="absolute bottom-0 inset-x-0 bg-blue-700/90 text-white text-[10px] text-center py-0.5">{{ __('Portada') }}</span>
                        @elseif ($this->puedeModificar())
                            <button type="button" wire:click="marcarPortada({{ $foto->id }})"
                                    class="absolute top-1 left-1 bg-slate-900/70 text-white rounded-full w-6 h-6 grid place-items-center text-xs font-bold"
                                    aria-label="{{ __('Usar como portada') }}" title="{{ __('Usar como portada') }}">★</button>
                        @endif

                        {{-- Siempre visible: en el celular no hay mouse para hacerlo aparecer. --}}
                        @if ($this->puedeBorrar($foto->user_id))
                            <button type="button" wire:click="eliminarFoto({{ $foto->id }})"
                                    wire:confirm="{{ __('¿Eliminar esta foto?') }}"
                                    class="absolute top-1 right-1 bg-slate-900/70 hover:bg-red-600 text-white rounded-full w-6 h-6 grid place-items-center text-xs font-bold"
                                    aria-label="{{ __('Eliminar') }}">✕</button>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Documentos: foto o PDF. En Android, un campo que acepta PDF suele abrir
         el gestor de archivos sin ofrecer la cámara: por eso también hay dos botones. --}}
    <div class="mt-6">
        <h4 class="etiqueta-seccion mb-3">{{ __('Documentos del vehículo') }}</h4>

        <div class="rounded-2xl border-2 border-dashed border-slate-300 p-5 text-center">
            <div class="flex flex-wrap justify-center gap-2">
                <label class="btn-primario btn-sm cursor-pointer">
                    <input type="file" wire:model="nuevosDocumentos" accept="image/*" capture="environment" multiple class="sr-only">
                    <x-icono nombre="camara" clase="w-4 h-4" /> {{ __('Tomar foto') }}
                </label>
                <label class="btn-secundario btn-sm cursor-pointer">
                    <input type="file" wire:model="nuevosDocumentos" accept="image/*,application/pdf" multiple class="sr-only">
                    <x-icono nombre="archivo" clase="w-4 h-4" /> {{ __('Subir archivo') }}
                </label>
            </div>
            <p class="text-xs text-slate-400 mt-2">{{ __('Foto o PDF, máx. 10 MB') }}</p>
            <p class="text-sm text-blue-700 font-medium mt-2" wire:loading wire:target="nuevosDocumentos">{{ __('Cargando archivos…') }}</p>
        </div>

        <x-input-error :messages="$errors->get('nuevosDocumentos')" class="mt-2" />
        <x-input-error :messages="$errors->get('nuevosDocumentos.*')" class="mt-2" />

        @if ($archivos->isNotEmpty())
            <ul class="mt-3 divide-y divide-slate-100">
                @foreach ($archivos as $documento)
                    <li class="py-2.5 flex items-center gap-3 text-sm" wire:key="documento-{{ $documento->id }}">
                        <x-icono nombre="archivo" clase="w-5 h-5 shrink-0 text-slate-400" />
                        <div class="min-w-0 flex-1">
                            <a href="{{ $documento->url() }}" target="_blank" rel="noopener"
                               class="font-semibold text-blue-700 hover:underline">
                                {{ $documento->esPdf() ? __('Ver PDF') : __('Ver foto') }}
                            </a>
                            <div class="text-xs text-slate-400 truncate">
                                {{ $documento->nombre_original ?? '—' }} · {{ $documento->usuario?->name ?? '—' }} · {{ $documento->created_at->format('d/m/Y') }}
                            </div>
                        </div>
                        @if ($this->puedeBorrar($documento->user_id))
                            <button type="button" wire:click="eliminarDocumento({{ $documento->id }})"
                                    wire:confirm="{{ __('¿Eliminar este documento?') }}"
                                    class="p-2 text-red-600 hover:bg-red-50 rounded-lg shrink-0" aria-label="{{ __('Eliminar') }}">
                                <x-icono nombre="basura" clase="w-4 h-4" />
                            </button>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>

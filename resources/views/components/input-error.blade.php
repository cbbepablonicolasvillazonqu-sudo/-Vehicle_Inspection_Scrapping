@props(['messages'])

@php
    // Aplana claves comodín (ej. $errors->get('fotos.*') devuelve arrays anidados).
    $mensajes = \Illuminate\Support\Arr::flatten((array) $messages);
@endphp

@if ($mensajes)
    <ul {{ $attributes->merge(['class' => 'text-sm text-red-600 space-y-1 mt-1']) }}>
        @foreach ($mensajes as $message)
            <li class="flex items-start gap-1"><span aria-hidden="true">⚠</span><span>{{ $message }}</span></li>
        @endforeach
    </ul>
@endif

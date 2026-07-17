@props(['messages'])

@if ($messages)
    <ul {{ $attributes->merge(['class' => 'text-sm text-red-600 space-y-1 mt-1']) }}>
        @foreach ((array) $messages as $message)
            <li class="flex items-start gap-1"><span aria-hidden="true">⚠</span><span>{{ $message }}</span></li>
        @endforeach
    </ul>
@endif

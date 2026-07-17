<button {{ $attributes->merge(['type' => 'submit', 'class' => 'btn bg-red-600 text-white shadow-sm shadow-red-600/20 hover:bg-red-700 focus:ring-red-500 px-4 py-2.5 text-sm']) }}>
    {{ $slot }}
</button>

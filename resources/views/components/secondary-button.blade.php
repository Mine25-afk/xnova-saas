<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center rounded-xl border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50']) }}>
    {{ $slot }}
</button>

<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center rounded-xl bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-red-700']) }}>
    {{ $slot }}
</button>

@props([
    'disabled' => false,
    'value' => null,
])

<input
    @disabled($disabled)
    {{ $attributes->merge([
        'class' => 'rounded-xl border border-gray-300 px-4 py-3 text-sm text-gray-900 shadow-sm focus:border-blue-500 focus:ring-blue-500',
        'value' => $value,
    ]) }}
>

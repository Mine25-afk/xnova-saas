@props([
    'name',
    'show' => false,
    'focusable' => false,
])

<div
    x-data="{ show: @js($show) }"
    x-on:open-modal.window="if ($event.detail === '{{ $name }}') show = true"
    x-on:close.window="show = false"
    x-on:keydown.escape.window="show = false"
    x-show="show"
    class="fixed inset-0 z-50 overflow-y-auto px-4 py-6 sm:px-0"
    style="display: none;"
>
    <div class="fixed inset-0 bg-slate-900/50" x-on:click="show = false"></div>

    <div class="relative mx-auto w-full max-w-2xl">
        <div class="overflow-hidden rounded-3xl bg-white shadow-2xl">
            {{ $slot }}
        </div>
    </div>
</div>

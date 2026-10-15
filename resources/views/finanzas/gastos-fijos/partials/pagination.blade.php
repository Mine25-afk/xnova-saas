@if ($gastos->lastPage() > 1)
    @if ($gastos->onFirstPage())
        <x-mini-button flat slate icon="chevron-left" disabled />
    @else
        <x-mini-button flat primary icon="chevron-left" :data-page="$gastos->currentPage() - 1" />
    @endif
    @for ($page = 1; $page <= $gastos->lastPage(); $page++)
        @if ($page === $gastos->currentPage())
            <x-mini-button primary :label="$page" :data-page="$page" />
        @elseif ($page === 1 || $page === $gastos->lastPage() || abs($page - $gastos->currentPage()) <= 1)
            <x-mini-button flat primary :label="$page" :data-page="$page" />
        @elseif ($page === 2 && $gastos->currentPage() > 3)
            <span class="list-feedback__meta">...</span>
        @elseif ($page === $gastos->lastPage() - 1 && $gastos->currentPage() < $gastos->lastPage() - 2)
            <span class="list-feedback__meta">...</span>
        @endif
    @endfor
    @if ($gastos->hasMorePages())
        <x-mini-button flat primary icon="chevron-right" :data-page="$gastos->currentPage() + 1" />
    @else
        <x-mini-button flat slate icon="chevron-right" disabled />
    @endif
@endif

@if ($ingresos->lastPage() > 1)
    @if ($ingresos->onFirstPage())
        <x-mini-button flat slate icon="chevron-left" disabled />
    @else
        <x-mini-button flat primary icon="chevron-left" :data-page="$ingresos->currentPage() - 1" />
    @endif
    @for ($page = 1; $page <= $ingresos->lastPage(); $page++)
        @if ($page === $ingresos->currentPage())
            <x-mini-button primary :label="$page" :data-page="$page" />
        @elseif ($page === 1 || $page === $ingresos->lastPage() || abs($page - $ingresos->currentPage()) <= 1)
            <x-mini-button flat primary :label="$page" :data-page="$page" />
        @elseif ($page === 2 && $ingresos->currentPage() > 3)
            <span class="list-feedback__meta">...</span>
        @elseif ($page === $ingresos->lastPage() - 1 && $ingresos->currentPage() < $ingresos->lastPage() - 2)
            <span class="list-feedback__meta">...</span>
        @endif
    @endfor
    @if ($ingresos->hasMorePages())
        <x-mini-button flat primary icon="chevron-right" :data-page="$ingresos->currentPage() + 1" />
    @else
        <x-mini-button flat slate icon="chevron-right" disabled />
    @endif
@endif

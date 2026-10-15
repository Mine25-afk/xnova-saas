@if ($movimientos->lastPage() > 1)
    @if ($movimientos->onFirstPage())
        <x-mini-button flat slate icon="chevron-left" disabled />
    @else
        <x-mini-button flat primary icon="chevron-left" :data-page="$movimientos->currentPage() - 1" />
    @endif
    @for ($page = 1; $page <= $movimientos->lastPage(); $page++)
        @if ($page === $movimientos->currentPage())
            <x-mini-button primary :label="$page" :data-page="$page" />
        @elseif ($page === 1 || $page === $movimientos->lastPage() || abs($page - $movimientos->currentPage()) <= 1)
            <x-mini-button flat primary :label="$page" :data-page="$page" />
        @elseif ($page === 2 && $movimientos->currentPage() > 3)
            <span class="list-feedback__meta">...</span>
        @elseif ($page === $movimientos->lastPage() - 1 && $movimientos->currentPage() < $movimientos->lastPage() - 2)
            <span class="list-feedback__meta">...</span>
        @endif
    @endfor
    @if ($movimientos->hasMorePages())
        <x-mini-button flat primary icon="chevron-right" :data-page="$movimientos->currentPage() + 1" />
    @else
        <x-mini-button flat slate icon="chevron-right" disabled />
    @endif
@endif

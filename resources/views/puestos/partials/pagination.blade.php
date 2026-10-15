@if ($puestos->lastPage() > 1)
    @if ($puestos->onFirstPage())
        <x-mini-button flat slate icon="chevron-left" disabled />
    @else
        <x-mini-button flat primary icon="chevron-left" :data-page="$puestos->currentPage() - 1" />
    @endif

    @for ($page = 1; $page <= $puestos->lastPage(); $page++)
        @if ($page === $puestos->currentPage())
            <x-mini-button primary :label="$page" :data-page="$page" />
        @elseif ($page === 1 || $page === $puestos->lastPage() || abs($page - $puestos->currentPage()) <= 1)
            <x-mini-button flat primary :label="$page" :data-page="$page" />
        @elseif ($page === 2 && $puestos->currentPage() > 3)
            <span class="list-feedback__meta">...</span>
        @elseif ($page === $puestos->lastPage() - 1 && $puestos->currentPage() < $puestos->lastPage() - 2)
            <span class="list-feedback__meta">...</span>
        @endif
    @endfor

    @if ($puestos->hasMorePages())
        <x-mini-button flat primary icon="chevron-right" :data-page="$puestos->currentPage() + 1" />
    @else
        <x-mini-button flat slate icon="chevron-right" disabled />
    @endif
@endif

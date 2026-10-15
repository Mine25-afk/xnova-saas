@if ($propietarios->lastPage() > 1)
    @if ($propietarios->onFirstPage())
        <x-mini-button flat slate icon="chevron-left" disabled />
    @else
        <x-mini-button flat primary icon="chevron-left" :data-page="$propietarios->currentPage() - 1" />
    @endif

    @for ($page = 1; $page <= $propietarios->lastPage(); $page++)
        @if ($page === $propietarios->currentPage())
            <x-mini-button primary :label="$page" :data-page="$page" />
        @elseif ($page === 1 || $page === $propietarios->lastPage() || abs($page - $propietarios->currentPage()) <= 1)
            <x-mini-button flat primary :label="$page" :data-page="$page" />
        @elseif ($page === 2 && $propietarios->currentPage() > 3)
            <span class="list-feedback__meta">...</span>
        @elseif ($page === $propietarios->lastPage() - 1 && $propietarios->currentPage() < $propietarios->lastPage() - 2)
            <span class="list-feedback__meta">...</span>
        @endif
    @endfor

    @if ($propietarios->hasMorePages())
        <x-mini-button flat primary icon="chevron-right" :data-page="$propietarios->currentPage() + 1" />
    @else
        <x-mini-button flat slate icon="chevron-right" disabled />
    @endif
@endif

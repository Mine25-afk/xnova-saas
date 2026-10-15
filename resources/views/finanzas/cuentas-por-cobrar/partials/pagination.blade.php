@if ($items->lastPage() > 1)
    @if ($items->onFirstPage())
        <x-mini-button flat slate icon="chevron-left" disabled />
    @else
        <x-mini-button flat primary icon="chevron-left" :data-page="$items->currentPage() - 1" />
    @endif
    @for ($page = 1; $page <= $items->lastPage(); $page++)
        @if ($page === $items->currentPage())
            <x-mini-button primary :label="$page" :data-page="$page" />
        @elseif ($page === 1 || $page === $items->lastPage() || abs($page - $items->currentPage()) <= 1)
            <x-mini-button flat primary :label="$page" :data-page="$page" />
        @elseif ($page === 2 && $items->currentPage() > 3)
            <span class="list-feedback__meta">...</span>
        @elseif ($page === $items->lastPage() - 1 && $items->currentPage() < $items->lastPage() - 2)
            <span class="list-feedback__meta">...</span>
        @endif
    @endfor
    @if ($items->hasMorePages())
        <x-mini-button flat primary icon="chevron-right" :data-page="$items->currentPage() + 1" />
    @else
        <x-mini-button flat slate icon="chevron-right" disabled />
    @endif
@endif

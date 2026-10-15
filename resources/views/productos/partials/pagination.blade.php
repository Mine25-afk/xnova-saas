@if ($productos->lastPage() > 1)
    @if ($productos->onFirstPage())
        <x-mini-button flat slate icon="chevron-left" disabled />
    @else
        <x-mini-button flat primary icon="chevron-left" :data-page="$productos->currentPage() - 1" />
    @endif

    @for ($page = 1; $page <= $productos->lastPage(); $page++)
        @if ($page === $productos->currentPage())
            <x-mini-button primary :label="$page" :data-page="$page" />
        @elseif ($page === 1 || $page === $productos->lastPage() || abs($page - $productos->currentPage()) <= 1)
            <x-mini-button flat primary :label="$page" :data-page="$page" />
        @elseif ($page === 2 && $productos->currentPage() > 3)
            <span class="list-feedback__meta">...</span>
        @elseif ($page === $productos->lastPage() - 1 && $productos->currentPage() < $productos->lastPage() - 2)
            <span class="list-feedback__meta">...</span>
        @endif
    @endfor

    @if ($productos->hasMorePages())
        <x-mini-button flat primary icon="chevron-right" :data-page="$productos->currentPage() + 1" />
    @else
        <x-mini-button flat slate icon="chevron-right" disabled />
    @endif
@endif

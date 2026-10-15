@if ($data->lastPage() > 1)
    @if ($data->onFirstPage())
        <x-mini-button flat slate icon="chevron-left" disabled />
    @else
        <x-mini-button flat primary icon="chevron-left" :data-page="$data->currentPage() - 1" />
    @endif

    @for ($page = 1; $page <= $data->lastPage(); $page++)
        @if ($page === $data->currentPage())
            <x-mini-button primary :label="$page" :data-page="$page" />
        @elseif ($page === 1 || $page === $data->lastPage() || abs($page - $data->currentPage()) <= 1)
            <x-mini-button flat primary :label="$page" :data-page="$page" />
        @elseif ($page === 2 && $data->currentPage() > 3)
            <span class="list-feedback__meta">...</span>
        @elseif ($page === $data->lastPage() - 1 && $data->currentPage() < $data->lastPage() - 2)
            <span class="list-feedback__meta">...</span>
        @endif
    @endfor

    @if ($data->hasMorePages())
        <x-mini-button flat primary icon="chevron-right" :data-page="$data->currentPage() + 1" />
    @else
        <x-mini-button flat slate icon="chevron-right" disabled />
    @endif
@endif

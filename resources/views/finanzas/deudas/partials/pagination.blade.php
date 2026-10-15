@if ($deudas->lastPage() > 1)
    @if ($deudas->onFirstPage())
        <x-mini-button flat slate icon="chevron-left" disabled />
    @else
        <x-mini-button flat primary icon="chevron-left" :data-page="$deudas->currentPage() - 1" />
    @endif
    @for ($page = 1; $page <= $deudas->lastPage(); $page++)
        @if ($page === $deudas->currentPage())
            <x-mini-button primary :label="$page" :data-page="$page" />
        @elseif ($page === 1 || $page === $deudas->lastPage() || abs($page - $deudas->currentPage()) <= 1)
            <x-mini-button flat primary :label="$page" :data-page="$page" />
        @elseif ($page === 2 && $deudas->currentPage() > 3)
            <span class="list-feedback__meta">...</span>
        @elseif ($page === $deudas->lastPage() - 1 && $deudas->currentPage() < $deudas->lastPage() - 2)
            <span class="list-feedback__meta">...</span>
        @endif
    @endfor
    @if ($deudas->hasMorePages())
        <x-mini-button flat primary icon="chevron-right" :data-page="$deudas->currentPage() + 1" />
    @else
        <x-mini-button flat slate icon="chevron-right" disabled />
    @endif
@endif

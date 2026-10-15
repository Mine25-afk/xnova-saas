@if ($cuentas->lastPage() > 1)
    @if ($cuentas->onFirstPage())
        <x-mini-button flat slate icon="chevron-left" disabled />
    @else
        <x-mini-button flat primary icon="chevron-left" :data-page="$cuentas->currentPage() - 1" />
    @endif

    @for ($page = 1; $page <= $cuentas->lastPage(); $page++)
        @if ($page === $cuentas->currentPage())
            <x-mini-button primary :label="$page" :data-page="$page" />
        @elseif ($page === 1 || $page === $cuentas->lastPage() || abs($page - $cuentas->currentPage()) <= 1)
            <x-mini-button flat primary :label="$page" :data-page="$page" />
        @elseif ($page === 2 && $cuentas->currentPage() > 3)
            <span class="list-feedback__meta">...</span>
        @elseif ($page === $cuentas->lastPage() - 1 && $cuentas->currentPage() < $cuentas->lastPage() - 2)
            <span class="list-feedback__meta">...</span>
        @endif
    @endfor

    @if ($cuentas->hasMorePages())
        <x-mini-button flat primary icon="chevron-right" :data-page="$cuentas->currentPage() + 1" />
    @else
        <x-mini-button flat slate icon="chevron-right" disabled />
    @endif
@endif

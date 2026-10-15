@extends('layouts.main')

@section('title', 'Cuentas — xNova')
@section('page-title', 'Finanzas / Cuentas')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/listado.css') }}">
@endsection

@section('content')
<div class="list-page">
    <x-card class="list-card list-card--cards-only">
        <x-slot name="header">
            <div class="list-card-header">
                <div>
                    <div class="list-card-title">Cuentas</div>
                    <div class="list-card-subtitle">Registra bancos, efectivo, tarjetas o cuentas donde se mueve el dinero.</div>
                </div>

                <a href="{{ route('finanzas.cuentas.create') }}" class="btn btn-primary">
                    <svg viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
                    Nueva cuenta
                </a>
            </div>
        </x-slot>

        <form id="cuentasFilters" class="list-toolbar">
            <div class="list-search">
                <x-input id="cuentas-search" name="search" icon="magnifying-glass" placeholder="Buscar cuenta, propietario, tipo o moneda" />
            </div>

            <div class="list-filter">
                <x-native-select id="cuentas-propietario" name="propietario_id">
                    <option value="">Todos los propietarios</option>
                    @foreach ($propietarios as $p)
                        <option value="{{ $p->id }}">{{ $p->nombre }}</option>
                    @endforeach
                </x-native-select>
            </div>

            <div class="list-per-page">
                <x-native-select id="cuentas-per-page" name="per_page">
                    <option value="5">5</option>
                    <option value="10" selected>10</option>
                    <option value="15">15</option>
                    <option value="25">25</option>
                </x-native-select>
            </div>
        </form>

        <div class="totals-wrap" id="cuentasTotalWrap" style="display:none;">
            <div class="totals-card">
                <div class="totals-card__head">
                    <span class="totals-card__dot"></span>
                    <span class="totals-card__title">Saldo total de las cuentas filtradas</span>
                </div>
                <div class="totals-card__list" id="cuentasTotal"></div>
            </div>
        </div>

        <div class="list-cards-wrap">
            <div class="list-cards list-cards--premium" id="cuentasCards">
                <div class="list-loading">Cargando cuentas...</div>
            </div>
        </div>

        <div class="list-feedback">
            <div class="list-feedback__meta" id="cuentasMeta">Cargando...</div>
            <div class="list-pagination" id="cuentasPagination"></div>
        </div>
    </x-card>
</div>
@endsection

@section('scripts')
<script>
    $(function () {
        var endpoint = @json(route('finanzas.cuentas.data'));
        var $form = $('#cuentasFilters');
        var $searchInput = $('#cuentas-search');
        var $perPageInput = $('#cuentas-per-page');
        var $propietarioInput = $('#cuentas-propietario');
        var $cards = $('#cuentasCards');
        var $meta = $('#cuentasMeta');
        var $pagination = $('#cuentasPagination');
        var $totalWrap = $('#cuentasTotalWrap');
        var $total = $('#cuentasTotal');

        if (!$form.length) {
            return;
        }

        var currentPage = 1;
        var listState = window.listUtils.createStateHtml({ entity: 'cuentas', colSpan: 5 });

        function loadCuentas(page) {
            page = page || 1;
            currentPage = page;
            $cards.html(listState.loadingCards);

            $.ajax({
                url: endpoint,
                method: 'GET',
                data: {
                    page: page,
                    search: $.trim($searchInput.val()),
                    per_page: $perPageInput.val(),
                    propietario_id: $propietarioInput.val(),
                },
            }).done(function (response) {
                var cards = response.cards || '';
                var paginationMeta = response.meta || {};

                $cards.html($.trim(cards) !== '' ? cards : listState.emptyCards);
                $pagination.html(response.pagination || '');

                var totales = response.totales || [];
                if (totales.length) {
                    var html = totales.map(function (t) {
                        var monto = Number(t.monto || 0);
                        var color = monto < 0 ? 'is-negative' : (monto > 0 ? 'is-positive' : '');
                        return '<div class="totals-card__row ' + color + '">' +
                            '<span class="totals-card__currency">' + t.moneda + '</span>' +
                            '<strong class="totals-card__amount">' + Number(monto).toLocaleString('es-HN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + '</strong>' +
                            '</div>';
                    }).join('');
                    $total.html(html);
                    $totalWrap.show();
                } else {
                    $totalWrap.hide();
                }

                if (!paginationMeta.total) {
                    $meta.text('No hay cuentas registradas.');
                    $cards.html(listState.emptyCards);
                    return;
                }

                $meta.text('Mostrando ' + paginationMeta.from + '-' + paginationMeta.to + ' de ' + paginationMeta.total + ' cuentas');
            }).fail(function () {
                $cards.html('<div class="list-empty">No se pudo cargar el listado.</div>');
                $pagination.html('');
                $meta.text('Error cargando cuentas.');
                window.notify('error', 'Cuentas', 'No se pudo obtener la lista de cuentas.');
            });
        }

        window.listUtils.bindFilterControls({
            searchInput: $searchInput,
            perPageInput: $perPageInput,
            pagination: $pagination,
            getCurrentPage: function () { return currentPage; },
            loadPage: loadCuentas,
        });

        $propietarioInput.on('change', function () { loadCuentas(1); });

        window.listUtils.bindPersistentFilters({
            storageKey: 'cuentas',
            inputs: { search: $searchInput, per_page: $perPageInput, propietario_id: $propietarioInput },
        });

        loadCuentas();
    });
</script>
@endsection

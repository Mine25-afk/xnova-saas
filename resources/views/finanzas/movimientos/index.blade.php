@extends('layouts.main')

@section('title', 'Movimientos - xNova')
@section('page-title', 'Finanzas / Movimientos')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/listado.css') }}">
@endsection

@section('content')
<div class="list-page">
    <x-card class="list-card">
        <x-slot name="header">
            <div class="list-card-header">
                <div>
                    <div class="list-card-title">Movimientos de cuenta</div>
                    <div class="list-card-subtitle">Cada movimiento aumenta, reduce o transfiere saldo entre cuentas.</div>
                </div>

                <a href="{{ route('finanzas.movimientos.create') }}" class="btn btn-primary">
                    <svg viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
                    Nuevo movimiento
                </a>
            </div>
        </x-slot>

        <form id="movimientosFilters" class="list-toolbar">
            <div class="list-search"><x-input id="movimientos-search" name="search" icon="magnifying-glass" placeholder="Buscar movimiento, cuenta, tipo o categoria" /></div>
            <div class="list-per-page"><x-native-select id="movimientos-per-page" name="per_page"><option value="5">5</option><option value="10" selected>10</option><option value="15">15</option><option value="25">25</option></x-native-select></div>
        </form>

        <div class="list-table-wrap"><div class="list-table-container"><table class="list-table"><thead><tr><th>Movimiento</th><th>Cuenta</th><th>Propietario</th><th>Monto</th><th>Acciones</th></tr></thead><tbody id="movimientosTableBody"><tr><td colspan="5" class="list-loading-row">Cargando movimientos...</td></tr></tbody></table></div></div>
        <div class="list-cards-wrap"><div class="list-cards" id="movimientosCards"><div class="list-loading">Cargando movimientos...</div></div></div>
        <div class="list-feedback"><div class="list-feedback__meta" id="movimientosMeta">Cargando...</div><div class="list-pagination" id="movimientosPagination"></div></div>
    </x-card>
</div>
@endsection

@section('scripts')
<script>
    $(function () {
        var endpoint = @json(route('finanzas.movimientos.data'));
        var $searchInput = $('#movimientos-search');
        var $perPageInput = $('#movimientos-per-page');
        var $tableBody = $('#movimientosTableBody');
        var $cards = $('#movimientosCards');
        var $meta = $('#movimientosMeta');
        var $pagination = $('#movimientosPagination');
        var currentPage = 1;
        var listState = window.listUtils.createStateHtml({ entity: 'movimientos', colSpan: 5 });

        function loadMovimientos(page) {
            page = page || 1;
            currentPage = page;
            $tableBody.html(listState.loadingRow);
            $cards.html(listState.loadingCards);

            $.ajax({ url: endpoint, method: 'GET', data: { page: page, search: $.trim($searchInput.val()), per_page: $perPageInput.val() } })
                .done(function (response) {
                    var meta = response.meta || {};
                    $tableBody.html($.trim(response.rows || '') !== '' ? response.rows : listState.emptyRow);
                    $cards.html($.trim(response.cards || '') !== '' ? response.cards : listState.emptyCards);
                    $pagination.html(response.pagination || '');

                    if (!meta.total) {
                        $meta.text('No hay movimientos registrados.');
                        return;
                    }

                    $meta.text('Mostrando ' + meta.from + '-' + meta.to + ' de ' + meta.total + ' movimientos');
                }).fail(function () {
                    $tableBody.html('<tr><td colspan="5" class="list-empty-row">No se pudo cargar el listado.</td></tr>');
                    $cards.html('<div class="list-empty">No se pudo cargar el listado.</div>');
                    $pagination.html('');
                    $meta.text('Error cargando movimientos.');
                    window.notify('error', 'Movimientos', 'No se pudo obtener la lista de movimientos.');
                });
        }

        window.listUtils.bindFilterControls({ searchInput: $searchInput, perPageInput: $perPageInput, pagination: $pagination, getCurrentPage: function () { return currentPage; }, loadPage: loadMovimientos });
        window.listUtils.bindPersistentFilters({
            storageKey: 'movimientos',
            inputs: { search: $searchInput, per_page: $perPageInput },
        });
        loadMovimientos();
    });
</script>
@endsection

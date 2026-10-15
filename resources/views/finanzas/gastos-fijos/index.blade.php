@extends('layouts.main')

@section('title', 'Gastos Fijos — xNova')
@section('page-title', 'Finanzas / Gastos Fijos')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/listado.css') }}">
@endsection

@section('content')
<div class="list-page">
    <x-card class="list-card list-card--cards-only">
        <x-slot name="header">
            <div class="list-card-header">
                <div>
                    <div class="list-card-title">Gastos fijos</div>
                    <div class="list-card-subtitle">Controla renta, servicios, deudas, suscripciones y compromisos recurrentes.</div>
                </div>

                <a href="{{ route('finanzas.gastos-fijos.create') }}" class="btn btn-primary">
                    <svg viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
                    Nuevo gasto
                </a>
            </div>
        </x-slot>

        <form id="gastosFilters" class="list-toolbar">
            <div class="list-search">
                <x-input id="gastos-search" name="search" icon="magnifying-glass" placeholder="Buscar gasto o propietario" />
            </div>

            <div class="list-filter">
                <x-native-select id="gastos-propietario" name="propietario_id">
                    <option value="">Todos los propietarios</option>
                    @foreach ($propietarios as $p)
                        <option value="{{ $p->id }}">{{ $p->nombre }}</option>
                    @endforeach
                </x-native-select>
            </div>

            <div class="list-per-page">
                <x-native-select id="gastos-per-page" name="per_page">
                    <option value="5">5</option>
                    <option value="10" selected>10</option>
                    <option value="15">15</option>
                    <option value="25">25</option>
                </x-native-select>
            </div>
        </form>

        <div class="totals-wrap" id="gastosTotalWrap" style="display:none;">
            <div class="totals-card totals-card--negative">
                <div class="totals-card__head">
                    <span class="totals-card__dot totals-card__dot--negative"></span>
                    <span class="totals-card__title">Total de gastos fijos</span>
                </div>
                <div class="totals-card__list">
                    <div class="totals-card__row is-negative">
                        <strong class="totals-card__amount totals-card__amount--lg" id="gastosTotal">—</strong>
                    </div>
                </div>
            </div>
        </div>

        <div class="list-cards-wrap">
            <div class="list-cards list-cards--premium" id="gastosCards">
                <div class="list-loading">Cargando gastos...</div>
            </div>
        </div>

        <div class="list-feedback">
            <div class="list-feedback__meta" id="gastosMeta">Cargando...</div>
            <div class="list-pagination" id="gastosPagination"></div>
        </div>
    </x-card>
</div>
@endsection

@section('scripts')
<script>
    $(function () {
        var endpoint = @json(route('finanzas.gastos-fijos.data'));
        var $searchInput = $('#gastos-search');
        var $perPageInput = $('#gastos-per-page');
        var $propietarioInput = $('#gastos-propietario');
        var $cards = $('#gastosCards');
        var $meta = $('#gastosMeta');
        var $pagination = $('#gastosPagination');
        var $totalWrap = $('#gastosTotalWrap');
        var $total = $('#gastosTotal');
        var currentPage = 1;
        var listState = window.listUtils.createStateHtml({ entity: 'gastos', colSpan: 4 });

        function loadGastos(page) {
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
            })
                .done(function (response) {
                    var meta = response.meta || {};
                    $cards.html($.trim(response.cards || '') !== '' ? response.cards : listState.emptyCards);
                    $pagination.html(response.pagination || '');

                    if (response.total_label) {
                        $total.text(response.total_label);
                        $totalWrap.show();
                    } else {
                        $totalWrap.hide();
                    }

                    if (!meta.total) {
                        $meta.text('No hay gastos fijos registrados.');
                        return;
                    }

                    $meta.text('Mostrando ' + meta.from + '-' + meta.to + ' de ' + meta.total + ' gastos');
                }).fail(function () {
                    $cards.html('<div class="list-empty">No se pudo cargar el listado.</div>');
                    $pagination.html('');
                    $meta.text('Error cargando gastos.');
                    window.notify('error', 'Gastos fijos', 'No se pudo obtener la lista de gastos.');
                });
        }

        window.listUtils.bindFilterControls({ searchInput: $searchInput, perPageInput: $perPageInput, pagination: $pagination, getCurrentPage: function () { return currentPage; }, loadPage: loadGastos });
        $propietarioInput.on('change', function () { loadGastos(1); });
        window.listUtils.bindPersistentFilters({
            storageKey: 'gastos-fijos',
            inputs: { search: $searchInput, per_page: $perPageInput, propietario_id: $propietarioInput },
        });
        loadGastos();
    });
</script>
@endsection

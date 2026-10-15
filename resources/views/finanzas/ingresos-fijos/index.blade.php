@extends('layouts.main')

@section('title', 'Ingresos Fijos — xNova')
@section('page-title', 'Finanzas / Ingresos Fijos')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/listado.css') }}">
@endsection

@section('content')
<div class="list-page">
    <x-card class="list-card list-card--cards-only">
        <x-slot name="header">
            <div class="list-card-header">
                <div>
                    <div class="list-card-title">Ingresos fijos</div>
                    <div class="list-card-subtitle">Registra sueldos, aportes o entradas recurrentes para proyectar el mes.</div>
                </div>

                <a href="{{ route('finanzas.ingresos-fijos.create') }}" class="btn btn-primary">
                    <svg viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
                    Nuevo ingreso
                </a>
            </div>
        </x-slot>

        <form id="ingresosFilters" class="list-toolbar">
            <div class="list-search">
                <x-input id="ingresos-search" name="search" icon="magnifying-glass" placeholder="Buscar ingreso, propietario o cuenta" />
            </div>

            <div class="list-filter">
                <x-native-select id="ingresos-propietario" name="propietario_id">
                    <option value="">Todos los propietarios</option>
                    @foreach ($propietarios as $p)
                        <option value="{{ $p->id }}">{{ $p->nombre }}</option>
                    @endforeach
                </x-native-select>
            </div>

            <div class="list-per-page">
                <x-native-select id="ingresos-per-page" name="per_page">
                    <option value="5">5</option>
                    <option value="10" selected>10</option>
                    <option value="15">15</option>
                    <option value="25">25</option>
                </x-native-select>
            </div>
        </form>

        <div class="totals-wrap" id="ingresosTotalWrap" style="display:none;">
            <div class="totals-card totals-card--positive">
                <div class="totals-card__head">
                    <span class="totals-card__dot totals-card__dot--positive"></span>
                    <span class="totals-card__title">Total de ingresos fijos</span>
                </div>
                <div class="totals-card__list">
                    <div class="totals-card__row is-positive">
                        <strong class="totals-card__amount totals-card__amount--lg" id="ingresosTotal">—</strong>
                    </div>
                </div>
            </div>
        </div>

        <div class="list-cards-wrap">
            <div class="list-cards list-cards--premium" id="ingresosCards">
                <div class="list-loading">Cargando ingresos...</div>
            </div>
        </div>

        <div class="list-feedback">
            <div class="list-feedback__meta" id="ingresosMeta">Cargando...</div>
            <div class="list-pagination" id="ingresosPagination"></div>
        </div>
    </x-card>
</div>
@endsection

@section('scripts')
<script>
    $(function () {
        var endpoint = @json(route('finanzas.ingresos-fijos.data'));
        var $searchInput = $('#ingresos-search');
        var $perPageInput = $('#ingresos-per-page');
        var $propietarioInput = $('#ingresos-propietario');
        var $cards = $('#ingresosCards');
        var $meta = $('#ingresosMeta');
        var $pagination = $('#ingresosPagination');
        var $totalWrap = $('#ingresosTotalWrap');
        var $total = $('#ingresosTotal');
        var currentPage = 1;
        var listState = window.listUtils.createStateHtml({ entity: 'ingresos', colSpan: 5 });

        function loadIngresos(page) {
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
                    $meta.text('No hay ingresos fijos registrados.');
                    return;
                }

                $meta.text('Mostrando ' + meta.from + '-' + meta.to + ' de ' + meta.total + ' ingresos');
            }).fail(function () {
                $cards.html('<div class="list-empty">No se pudo cargar el listado.</div>');
                $pagination.html('');
                $meta.text('Error cargando ingresos.');
                window.notify('error', 'Ingresos fijos', 'No se pudo obtener la lista de ingresos.');
            });
        }

        window.listUtils.bindFilterControls({
            searchInput: $searchInput,
            perPageInput: $perPageInput,
            pagination: $pagination,
            getCurrentPage: function () { return currentPage; },
            loadPage: loadIngresos,
        });

        $propietarioInput.on('change', function () { loadIngresos(1); });

        window.listUtils.bindPersistentFilters({
            storageKey: 'ingresos-fijos',
            inputs: { search: $searchInput, per_page: $perPageInput, propietario_id: $propietarioInput },
        });

        loadIngresos();
    });
</script>
@endsection

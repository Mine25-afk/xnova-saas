@extends('layouts.main')

@section('title', 'Propietarios de Cuenta — xNova')
@section('page-title', 'Finanzas / Propietarios de Cuenta')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/listado.css') }}">
@endsection

@section('content')
<div class="list-page">
    <x-card class="list-card list-card--cards-only">
        <x-slot name="header">
            <div class="list-card-header">
                <div>
                    <div class="list-card-title">Propietarios de cuenta</div>
                    <div class="list-card-subtitle">Personas que participan en las finanzas de la empresa.</div>
                </div>

                <a href="{{ route('finanzas.propietarios.create') }}" class="btn btn-primary">
                    <svg viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
                    Nuevo propietario
                </a>
            </div>
        </x-slot>

        <form id="propietariosFilters" class="list-toolbar">
            <div class="list-search">
                <x-input
                    id="propietarios-search"
                    name="search"
                    icon="magnifying-glass"
                    placeholder="Buscar propietario"
                />
            </div>

            <div class="list-per-page">
                <x-native-select id="propietarios-per-page" name="per_page">
                    <option value="5">5</option>
                    <option value="10" selected>10</option>
                    <option value="15">15</option>
                    <option value="25">25</option>
                </x-native-select>
            </div>
        </form>

        <div class="list-cards-wrap">
            <div class="list-cards list-cards--premium" id="propietariosCards">
                <div class="list-loading">Cargando propietarios...</div>
            </div>
        </div>

        <div class="list-feedback">
            <div class="list-feedback__meta" id="propietariosMeta">Cargando...</div>
            <div class="list-pagination" id="propietariosPagination"></div>
        </div>
    </x-card>
</div>
@endsection

@section('scripts')
<script>
    $(function () {
        var endpoint = @json(route('finanzas.propietarios.data'));
        var $form = $('#propietariosFilters');
        var $searchInput = $('#propietarios-search');
        var $perPageInput = $('#propietarios-per-page');
        var $cards = $('#propietariosCards');
        var $meta = $('#propietariosMeta');
        var $pagination = $('#propietariosPagination');

        if (!$form.length) {
            return;
        }

        var currentPage = 1;
        var listState = window.listUtils.createStateHtml({
            entity: 'propietarios',
            colSpan: 3
        });

        function loadPropietarios(page) {
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
                },
            }).done(function (response) {
                var cards = response.cards || '';
                var paginationHtml = response.pagination || '';
                var paginationMeta = response.meta || {};

                $cards.html($.trim(cards) !== '' ? cards : listState.emptyCards);
                $pagination.html(paginationHtml);

                if (!paginationMeta.total) {
                    $meta.text('No hay propietarios registrados.');
                    $cards.html(listState.emptyCards);
                    return;
                }

                $meta.text('Mostrando ' + paginationMeta.from + '-' + paginationMeta.to + ' de ' + paginationMeta.total + ' propietarios');
            }).fail(function () {
                $cards.html('<div class="list-empty">No se pudo cargar el listado.</div>');
                $pagination.html('');
                $meta.text('Error cargando propietarios.');
                window.notify('error', 'Propietarios', 'No se pudo obtener la lista de propietarios.');
            });
        }

        window.listUtils.bindFilterControls({
            searchInput: $searchInput,
            perPageInput: $perPageInput,
            pagination: $pagination,
            getCurrentPage: function () {
                return currentPage;
            },
            loadPage: loadPropietarios,
        });

        window.listUtils.bindPersistentFilters({
            storageKey: 'propietarios',
            inputs: { search: $searchInput, per_page: $perPageInput },
        });

        loadPropietarios();
    });
</script>
@endsection

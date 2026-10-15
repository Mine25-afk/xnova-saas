@extends('layouts.main')

@section('title', 'Productos - xNova')
@section('page-title', 'Inventario / Productos')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/listado.css') }}">
    <style>
        .product-cell{display:flex;align-items:center;gap:14px}
        .product-thumb{width:56px;height:56px;border-radius:16px;object-fit:cover;border:1px solid rgba(148,163,184,.2);background:linear-gradient(135deg,#eff6ff,#dbeafe);flex-shrink:0}
        .product-thumb-placeholder{display:grid;place-items:center;font-weight:800;color:#1d4ed8}
        .product-meta{display:grid;gap:4px}
        .product-status{display:inline-flex;align-items:center;width:fit-content;padding:6px 10px;border-radius:999px;font-size:.76rem;font-weight:700}
        .product-status.is-active{background:rgba(16,185,129,.12);color:#047857}
        .product-status.is-inactive{background:rgba(148,163,184,.16);color:#475569}
        .product-price{font-weight:700;color:#0f172a}
        .product-image-note{font-size:.78rem;color:#64748b}
    </style>
@endsection

@section('content')
<div class="list-page">
    <x-card class="list-card">
        <x-slot name="header">
            <div class="list-card-header">
                <div>
                    <div class="list-card-title">Productos</div>
                    <div class="list-card-subtitle">Administra tu catalogo de productos visibles en la reservacion web y dentro del inventario.</div>
                </div>

                <div class="form-toolbar">
                    <a href="{{ route('inventario.reservacion') }}" class="btn btn-secondary">
                        <svg viewBox="0 0 24 24"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20z"/></svg>
                        Ver reservacion
                    </a>
                    <a href="{{ route('inventario.productos.create') }}" class="btn btn-primary">
                        <svg viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
                        Nuevo producto
                    </a>
                </div>
            </div>
        </x-slot>

        <form id="productosFilters" class="list-toolbar">
            <div class="list-search">
                <x-input
                    id="productos-search"
                    name="search"
                    icon="magnifying-glass"
                    placeholder="Buscar producto o descripcion"
                />
            </div>

            <div class="list-per-page">
                <x-native-select id="productos-per-page" name="per_page">
                    <option value="5">5</option>
                    <option value="10" selected>10</option>
                    <option value="15">15</option>
                    <option value="25">25</option>
                </x-native-select>
            </div>
        </form>

        <div class="list-table-wrap">
            <div class="list-table-container">
                <table class="list-table">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Descripcion</th>
                            <th>Estado</th>
                            <th>Precio</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="productosTableBody">
                        <tr>
                            <td colspan="5" class="list-loading-row">Cargando productos...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="list-cards-wrap">
            <div class="list-cards" id="productosCards">
                <div class="list-loading">Cargando productos...</div>
            </div>
        </div>

        <div class="list-feedback">
            <div class="list-feedback__meta" id="productosMeta">Cargando...</div>
            <div class="list-pagination" id="productosPagination"></div>
        </div>
    </x-card>
</div>
@endsection

@section('scripts')
<script>
    $(function () {
        var endpoint = @json(route('inventario.productos.data'));
        var $form = $('#productosFilters');
        var $searchInput = $('#productos-search');
        var $perPageInput = $('#productos-per-page');
        var $tableBody = $('#productosTableBody');
        var $cards = $('#productosCards');
        var $meta = $('#productosMeta');
        var $pagination = $('#productosPagination');

        if (!$form.length) {
            return;
        }

        var currentPage = 1;
        var listState = window.utilidadesListado.crearEstadoHtml({
            entity: 'productos',
            colSpan: 5
        });

        function loadProductos(page) {
            page = page || 1;
            currentPage = page;

            $tableBody.html(listState.loadingRow);
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
                var rows = response.rows || '';
                var cards = response.cards || '';
                var paginationHtml = response.pagination || '';
                var paginationMeta = response.meta || {};

                $tableBody.html($.trim(rows) !== '' ? rows : listState.emptyRow);
                $cards.html($.trim(cards) !== '' ? cards : listState.emptyCards);
                $pagination.html(paginationHtml);

                if (!paginationMeta.total) {
                    $meta.text('No hay productos registrados.');
                    $tableBody.html(listState.emptyRow);
                    $cards.html(listState.emptyCards);
                    return;
                }

                $meta.text('Mostrando ' + paginationMeta.from + '-' + paginationMeta.to + ' de ' + paginationMeta.total + ' productos');
            }).fail(function () {
                $tableBody.html(listState.emptyRow);
                $cards.html(listState.emptyCards);
                $pagination.html('');
                $meta.text('Error cargando productos.');
                window.notify('error', 'Productos', 'No se pudo obtener la lista de productos.');
            });
        }

        window.utilidadesListado.vincularControlesFiltros({
            searchInput: $searchInput,
            perPageInput: $perPageInput,
            pagination: $pagination,
            getCurrentPage: function () {
                return currentPage;
            },
            loadPage: loadProductos,
        });

        loadProductos();
    });
</script>
@endsection

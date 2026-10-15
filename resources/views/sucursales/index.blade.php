@extends('layouts.main')

@section('title', 'Sucursales - xNova')
@section('page-title', 'Configuracion / Sucursales')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/listado.css') }}">
@endsection

@section('content')
@php
    $permissionCodes = $currentUserPermissionCodes ?? [];
    $canCreate = in_array('configuracion.sucursales.crear', $permissionCodes, true);
@endphp
<div class="list-page">
    <x-card class="list-card">
        <x-slot name="header">
            <div class="list-card-header">
                <div>
                    <div class="list-card-title">Sucursales</div>
                    <div class="list-card-subtitle">Administra las sucursales de la empresa y consulta sus datos principales.</div>
                </div>

                @if ($canCreate)
                    <a href="{{ route('sucursales.create') }}" class="btn btn-primary">
                        <svg viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
                        Nueva sucursal
                    </a>
                @endif
            </div>
        </x-slot>

        <form id="filtrosSucursales" class="list-toolbar">
            <div class="list-search">
                <x-input
                    id="sucursales-search"
                    name="search"
                    icon="magnifying-glass"
                    placeholder="Buscar sucursal"
                />
            </div>

            <div class="list-per-page">
                <x-native-select id="sucursales-per-page" name="per_page">
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
                            <th>Sucursal</th>
                            <th>Empleados</th>
                            <th>Usuarios</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="sucursalesTableBody">
                        <tr>
                            <td colspan="4" class="list-loading-row">Cargando sucursales...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="list-cards-wrap">
            <div class="list-cards" id="sucursalesCards">
                <div class="list-loading">Cargando sucursales...</div>
            </div>
        </div>

        <div class="list-feedback">
            <div class="list-feedback__meta" id="sucursalesMeta">Cargando...</div>
            <div class="list-pagination" id="sucursalesPagination"></div>
        </div>
    </x-card>
</div>

<div class="list-modal" id="sucursalesActionModal" aria-hidden="true">
    <div class="list-modal__backdrop" data-close-modal></div>
    <div class="list-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="sucursalesActionModalTitle">
        <div>
            <div class="list-modal__title" id="sucursalesActionModalTitle">Acciones de la sucursal</div>
            <p class="list-modal__copy" id="sucursalesActionModalCopy"></p>
        </div>

        <div class="list-modal__actions">
            <a href="#" id="sucursalesModalShow" class="list-action list-action--info">Detalles</a>
            <a href="#" id="sucursalesModalEdit" class="list-action list-action--edit">Editar</a>
            <button type="button" class="btn btn-secondary" data-close-modal>Cerrar</button>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(function () {
        var urlDatos = @json(route('sucursales.data'));
        var $form = $('#filtrosSucursales');
        var $inputBusqueda = $form.find('[name="search"]');
        var $inputRegistros = $form.find('[name="per_page"]');
        var $tableBody = $('#sucursalesTableBody');
        var $cards = $('#sucursalesCards');
        var $meta = $('#sucursalesMeta');
        var $pagination = $('#sucursalesPagination');
        var $modal = $('#sucursalesActionModal');
        var $modalCopy = $('#sucursalesActionModalCopy');
        var $modalShow = $('#sucursalesModalShow');
        var $modalEdit = $('#sucursalesModalEdit');

        if (!$form.length) {
            return;
        }

        var paginaActual = 1;
        var estadoListado = window.utilidadesListado.crearEstadoHtml({
            entity: 'sucursales',
            colSpan: 4
        });

        function esVerdadero(valor) {
            return Number(valor) === 1 || valor === true || valor === 'true';
        }

        function cerrarModalAcciones() {
            window.utilidadesFormulario.cerrarModal($modal);
        }

        function abrirModalAcciones(payload) {
            var puedeVer = esVerdadero(payload.canShow);
            var puedeEditar = esVerdadero(payload.canEdit);

            if (!puedeVer && !puedeEditar) {
                return;
            }

            $modalCopy.text('Selecciona que deseas hacer con "' + payload.name + '".');
            $modalShow.attr('href', payload.showUrl || '#').toggle(puedeVer);
            $modalEdit.attr('href', payload.editUrl || '#').toggle(puedeEditar);
            $modal.addClass('is-open').attr('aria-hidden', 'false');
        }

        function cargarSucursales(page) {
            page = page || 1;
            paginaActual = page;

            $tableBody.html(estadoListado.loadingRow);
            $cards.html(estadoListado.loadingCards);

            $.ajax({
                url: urlDatos,
                method: 'GET',
                data: {
                    page: page,
                    search: $.trim($inputBusqueda.val()),
                    per_page: $inputRegistros.val(),
                },
            }).done(function (response) {
                var rows = response.rows || '';
                var cards = response.cards || '';
                var paginationHtml = response.pagination || '';
                var paginationMeta = response.meta || {};

                $tableBody.html($.trim(rows) !== '' ? rows : estadoListado.emptyRow);
                $cards.html($.trim(cards) !== '' ? cards : estadoListado.emptyCards);
                $pagination.html(paginationHtml);

                if (!paginationMeta.total) {
                    $meta.text('No hay sucursales registradas.');
                    $tableBody.html(estadoListado.emptyRow);
                    $cards.html(estadoListado.emptyCards);
                    return;
                }

                $meta.text('Mostrando ' + paginationMeta.from + '-' + paginationMeta.to + ' de ' + paginationMeta.total + ' sucursales');
            }).fail(function () {
                $tableBody.html(estadoListado.emptyRow);
                $cards.html(estadoListado.emptyCards);
                $pagination.html('');
                $meta.text('Error cargando sucursales.');
                window.notify('error', 'Error', 'No se pudo obtener la lista de sucursales.');
            });
        }

        window.utilidadesListado.vincularControlesFiltros({
            searchInput: $inputBusqueda,
            perPageInput: $inputRegistros,
            pagination: $pagination,
            getCurrentPage: function () {
                return paginaActual;
            },
            loadPage: cargarSucursales,
        });

        $cards.on('click', '.js-sucursal-card', function () {
            abrirModalAcciones($(this).data());
        });

        $modal.on('click', '[data-close-modal]', function () {
            cerrarModalAcciones();
        });

        $(document).on('keydown', function (event) {
            if (event.key === 'Escape') {
                cerrarModalAcciones();
            }
        });

        cargarSucursales();
    });
</script>
@endsection

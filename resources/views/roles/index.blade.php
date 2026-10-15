@extends('layouts.main')

@section('title', 'Roles - xNova')
@section('page-title', 'Seguridad / Roles')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/listado.css') }}">
@endsection

@section('content')
@php
    $canCreateRole = in_array('seguridad.roles.crear', $currentUserPermissionCodes ?? [], true);
@endphp
<div class="list-page">
    <x-card class="list-card">
        <x-slot name="header">
            <div class="list-card-header">
                <div>
                    <div class="list-card-title">Roles</div>
                    <div class="list-card-subtitle">Administra roles y los permisos que podran usar dentro del sistema.</div>
                </div>

                @if ($canCreateRole)
                    <a href="{{ route('roles.create') }}" class="btn btn-primary">
                        <svg viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
                        Nuevo rol
                    </a>
                @endif
            </div>
        </x-slot>

        <form id="filtrosRoles" class="list-toolbar">
            <div class="list-search">
                <x-input
                    id="roles-search"
                    name="search"
                    icon="magnifying-glass"
                    placeholder="Buscar rol"
                />
            </div>

            <div class="list-per-page">
                <x-native-select id="roles-per-page" name="per_page">
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
                            <th>Rol</th>
                            <th>Permisos</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="rolesTableBody">
                        <tr>
                            <td colspan="3" class="list-loading-row">Cargando roles...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="list-cards-wrap">
            <div class="list-cards" id="rolesCards">
                <div class="list-loading">Cargando roles...</div>
            </div>
        </div>

        <div class="list-feedback">
            <div class="list-feedback__meta" id="rolesMeta">Cargando...</div>
            <div class="list-pagination" id="rolesPagination"></div>
        </div>
    </x-card>
</div>

<div class="list-modal" id="rolesActionModal" aria-hidden="true">
    <div class="list-modal__backdrop" data-close-modal></div>
    <div class="list-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="rolesActionModalTitle">
        <div>
            <div class="list-modal__title" id="rolesActionModalTitle">Acciones del rol</div>
            <p class="list-modal__copy" id="rolesActionModalCopy"></p>
        </div>

        <div class="list-modal__actions">
            <a href="#" id="rolesModalShow" class="list-action list-action--info">Detalles</a>
            <a href="#" id="rolesModalEdit" class="list-action list-action--edit">Editar</a>
            <button type="button" class="btn btn-secondary" data-close-modal>Cerrar</button>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(function () {
        var urlDatos = @json(route('roles.data'));
        var $form = $('#filtrosRoles');
        var $inputBusqueda = $form.find('[name="search"]');
        var $inputRegistros = $form.find('[name="per_page"]');
        var $tableBody = $('#rolesTableBody');
        var $cards = $('#rolesCards');
        var $meta = $('#rolesMeta');
        var $pagination = $('#rolesPagination');
        var $modal = $('#rolesActionModal');
        var $modalCopy = $('#rolesActionModalCopy');
        var $modalShow = $('#rolesModalShow');
        var $modalEdit = $('#rolesModalEdit');

        if (!$form.length) {
            return;
        }

        var paginaActual = 1;
        var estadoListado = window.utilidadesListado.crearEstadoHtml({
            entity: 'roles',
            colSpan: 3
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
            $modalEdit.attr('href', payload.editUrl || '#');
            $modalEdit.toggle(puedeEditar);
            $modal.addClass('is-open').attr('aria-hidden', 'false');
        }

        function cargarRoles(page) {
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
                    $meta.text('No hay roles registrados.');
                    $tableBody.html(estadoListado.emptyRow);
                    $cards.html(estadoListado.emptyCards);
                    return;
                }

                $meta.text('Mostrando ' + paginationMeta.from + '-' + paginationMeta.to + ' de ' + paginationMeta.total + ' roles');
            }).fail(function () {
                $tableBody.html(estadoListado.emptyRow);
                $cards.html(estadoListado.emptyCards);
                $pagination.html('');
                $meta.text('Error cargando roles.');
                window.notify('error', 'Error', 'No se pudo obtener la lista de roles.');
            });
        }

        window.utilidadesListado.vincularControlesFiltros({
            searchInput: $inputBusqueda,
            perPageInput: $inputRegistros,
            pagination: $pagination,
            getCurrentPage: function () {
                return paginaActual;
            },
            loadPage: cargarRoles,
        });

        $cards.on('click', '.js-rol-card', function () {
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

        cargarRoles();
    });
</script>
@endsection

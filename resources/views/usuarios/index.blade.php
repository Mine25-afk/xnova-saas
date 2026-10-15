@extends('layouts.main')

@section('title', 'Usuarios - xNova')
@section('page-title', 'Seguridad / Usuarios')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/listado.css') }}">
@endsection

@section('content')
@php
    $canCreateUser = in_array('seguridad.usuarios.crear', $currentUserPermissionCodes ?? [], true);
@endphp
<div class="list-page">
    <x-card class="list-card">
        <x-slot name="header">
            <div class="list-card-header">
                <div>
                    <div class="list-card-title">Usuarios</div>
                    <div class="list-card-subtitle">Administra usuarios, su rol, su sucursal y el estado de acceso al sistema.</div>
                </div>

                @if ($canCreateUser)
                    <a href="{{ route('usuarios.create') }}" class="btn btn-primary">
                        <svg viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
                        Nuevo usuario
                    </a>
                @endif
            </div>
        </x-slot>

        <form id="filtrosUsuarios" class="list-toolbar">
            <div class="list-search">
                <x-input
                    id="usuarios-search"
                    name="search"
                    icon="magnifying-glass"
                    placeholder="Buscar usuario, gmail, rol o sucursal"
                />
            </div>

            <div class="list-per-page">
                <x-native-select id="usuarios-per-page" name="per_page">
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
                            <th>Usuario</th>
                            <th>Rol</th>
                            <th>Sucursal</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="usuariosTableBody">
                        <tr>
                            <td colspan="5" class="list-loading-row">Cargando usuarios...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="list-cards-wrap">
            <div class="list-cards" id="usuariosCards">
                <div class="list-loading">Cargando usuarios...</div>
            </div>
        </div>

        <div class="list-feedback">
            <div class="list-feedback__meta" id="usuariosMeta">Cargando...</div>
            <div class="list-pagination" id="usuariosPagination"></div>
        </div>
    </x-card>
</div>

<div class="list-modal" id="usuariosActionModal" aria-hidden="true">
    <div class="list-modal__backdrop" data-close-modal></div>
    <div class="list-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="usuariosActionModalTitle">
        <div>
            <div class="list-modal__title" id="usuariosActionModalTitle">Acciones del usuario</div>
            <p class="list-modal__copy" id="usuariosActionModalCopy"></p>
        </div>

        <div class="list-modal__actions">
            <a href="#" id="usuariosModalShow" class="list-action list-action--info">Detalles</a>
            <a href="#" id="usuariosModalEdit" class="list-action list-action--edit">Editar</a>
            <button type="button" id="usuariosModalToggle" class="list-action list-action--danger"></button>
            <button type="button" class="btn btn-secondary" data-close-modal>Cerrar</button>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(function () {
        var endpoint = @json(route('usuarios.data'));
        var csrfToken = $('meta[name="csrf-token"]').attr('content');
        var $form = $('#filtrosUsuarios');
        var $inputBusqueda = $form.find('[name="search"]');
        var $inputRegistros = $form.find('[name="per_page"]');
        var $tableBody = $('#usuariosTableBody');
        var $cards = $('#usuariosCards');
        var $meta = $('#usuariosMeta');
        var $pagination = $('#usuariosPagination');
        var $modal = $('#usuariosActionModal');
        var $modalCopy = $('#usuariosActionModalCopy');
        var $modalShow = $('#usuariosModalShow');
        var $modalEdit = $('#usuariosModalEdit');
        var $modalToggle = $('#usuariosModalToggle');

        if (!$form.length) {
            return;
        }

        var paginaActual = 1;
        var estadoListado = window.utilidadesListado.crearEstadoHtml({
            entidad: 'usuarios',
            colSpan: 5
        });

        function esVerdadero(valor) {
            return Number(valor) === 1 || valor === true || valor === 'true';
        }

        function cerrarModalAcciones() {
            window.utilidadesFormulario.cerrarModal($modal);
        }

        function abrirModalAcciones(payload) {
            var estaActivo = esVerdadero(payload.active);
            var puedeVer = esVerdadero(payload.canShow);
            var puedeEditar = esVerdadero(payload.canEdit);
            var puedeCambiarEstado = esVerdadero(payload.canToggle);

            $modalCopy.text('Selecciona qué deseas hacer con "' + payload.name + '". Estado actual: ' + (estaActivo ? 'Activo' : 'Inactivo') + '.');

            $modalShow.attr('href', payload.showUrl || '#').toggle(puedeVer);
            $modalEdit.attr('href', payload.editUrl || '#').toggle(puedeEditar);

            if (puedeCambiarEstado) {
                $modalToggle
                    .text(estaActivo ? 'Desactivar' : 'Activar')
                    .removeClass('list-action--danger list-action--success')
                    .addClass(estaActivo ? 'list-action--danger' : 'list-action--success')
                    .data('payload', payload)
                    .show();
            } else {
                $modalToggle.hide().data('payload', null);
            }

            $modal.addClass('is-open').attr('aria-hidden', 'false');
        }

        function obtenerMensajeConfirmacion(payload) {
            if (esVerdadero(payload.active)) {
                return 'Si desactivas este usuario, ya no podrá ingresar al sistema hasta que vuelvas a activarlo.';
            }

            return 'Si activas este usuario, volverá a poder ingresar al sistema.';
        }

        function ejecutarCambioEstado(payload) {
            $.ajax({
                url: payload.url,
                method: 'POST',
                data: {
                    _token: csrfToken,
                    _method: payload.method,
                },
            }).done(function (response) {
                cerrarModalAcciones();
                window.notify('success', 'Usuarios', response.message || 'Estado actualizado correctamente.');
                cargarUsuarios(paginaActual);
            }).fail(function (xhr) {
                var mensaje = xhr.responseJSON?.message || 'No se pudo actualizar el estado del usuario.';
                var tipo = xhr.status === 422 ? 'warning' : 'error';

                window.notify(tipo, 'Usuarios', mensaje);
            });
        }

        function confirmarCambioEstado(payload) {
            var estaActivo = esVerdadero(payload.active);
            var title = estaActivo ? '¿Desactivar usuario?' : '¿Activar usuario?';
            var acceptLabel = estaActivo ? 'Sí, desactivar' : 'Sí, activar';
            var acceptColor = estaActivo ? 'negative' : 'positive';

            if (window.$wireui && typeof window.$wireui.confirmDialog === 'function') {
                window.$wireui.confirmDialog({
                    title: title,
                    description: obtenerMensajeConfirmacion(payload),
                    icon: estaActivo ? 'warning' : 'question',
                    accept: {
                        label: acceptLabel,
                        color: acceptColor,
                        execute: function () {
                            ejecutarCambioEstado(payload);
                        },
                    },
                    reject: {
                        label: 'Cancelar',
                    },
                });

                return;
            }

            if (window.confirm(title + '\n\n' + obtenerMensajeConfirmacion(payload))) {
                ejecutarCambioEstado(payload);
            }
        }

        function cargarUsuarios(page) {
            page = page || 1;
            paginaActual = page;

            $tableBody.html(estadoListado.loadingRow);
            $cards.html(estadoListado.loadingCards);

            $.ajax({
                url: endpoint,
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
                    $meta.text('No hay usuarios registrados.');
                    $tableBody.html(estadoListado.emptyRow);
                    $cards.html(estadoListado.emptyCards);
                    return;
                }

                $meta.text('Mostrando ' + paginationMeta.from + '-' + paginationMeta.to + ' de ' + paginationMeta.total + ' usuarios');
            }).fail(function () {
                $tableBody.html(estadoListado.emptyRow);
                $cards.html(estadoListado.emptyCards);
                $pagination.html('');
                $meta.text('Error cargando usuarios.');
                window.notify('error', 'Error', 'No se pudo obtener la lista de usuarios.');
            });
        }

        window.utilidadesListado.vincularControlesFiltros({
            searchInput: $inputBusqueda,
            perPageInput: $inputRegistros,
            pagination: $pagination,
            getCurrentPage: function () {
                return paginaActual;
            },
            loadPage: cargarUsuarios,
        });

        $(document).on('click', '.js-cambiar-estado-usuario', function () {
            confirmarCambioEstado($(this).data());
        });

        $cards.on('click', '.js-usuario-card', function () {
            abrirModalAcciones($(this).data());
        });

        $modalToggle.on('click', function () {
            var payload = $(this).data('payload');

            cerrarModalAcciones();

            if (!payload) {
                return;
            }

            confirmarCambioEstado(payload);
        });

        $modal.on('click', '[data-close-modal]', function () {
            cerrarModalAcciones();
        });

        $(document).on('keydown', function (event) {
            if (event.key === 'Escape') {
                cerrarModalAcciones();
            }
        });

        cargarUsuarios();
    });
</script>
@endsection

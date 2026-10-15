@extends('layouts.main')

@section('title', 'Empleados - xNova')
@section('page-title', 'Socios / Empleados')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/listado.css') }}">
@endsection

@section('content')
@php
    $permissionCodes = $currentUserPermissionCodes ?? [];
    $canCreate = in_array('socios.colaboradores.crear', $permissionCodes, true);
@endphp
<div class="list-page">
    <x-card class="list-card">
        <x-slot name="header">
            <div class="list-card-header">
                <div>
                    <div class="list-card-title">Empleados</div>
                    <div class="list-card-subtitle">Administra empleados, su puesto, su sucursal y su estado operativo.</div>
                </div>

                @if ($canCreate)
                    <a href="{{ route('socios.create', ['tipo' => 'E']) }}" class="btn btn-primary">
                        <svg viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
                        Nuevo empleado
                    </a>
                @endif
            </div>
        </x-slot>

        <form id="filtrosEmpleados" class="list-toolbar">
            <div class="list-search">
                <x-input
                    id="empleados-search"
                    name="search"
                    icon="magnifying-glass"
                    placeholder="Buscar empleado"
                />
            </div>

            <div class="list-per-page">
                <x-native-select id="empleados-per-page" name="per_page">
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
                            <th>Empleado</th>
                            <th>Identidad</th>
                            <th>Correo</th>
                            <th>Puesto</th>
                            <th>Sucursal</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="empleadosTableBody">
                        <tr>
                            <td colspan="7" class="list-loading-row">Cargando empleados...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="list-cards-wrap">
            <div class="list-cards" id="empleadosCards">
                <div class="list-loading">Cargando empleados...</div>
            </div>
        </div>

        <div class="list-feedback">
            <div class="list-feedback__meta" id="empleadosMeta">Cargando...</div>
            <div class="list-pagination" id="empleadosPagination"></div>
        </div>
    </x-card>
</div>

<div class="list-modal" id="empleadosActionModal" aria-hidden="true">
    <div class="list-modal__backdrop" data-close-modal></div>
    <div class="list-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="empleadosActionModalTitle">
        <div>
            <div class="list-modal__title" id="empleadosActionModalTitle">Acciones del empleado</div>
            <p class="list-modal__copy" id="empleadosActionModalCopy"></p>
        </div>

        <div class="list-modal__actions">
            <a href="#" id="empleadosModalShow" class="list-action list-action--info">Detalles</a>
            <a href="#" id="empleadosModalEdit" class="list-action list-action--edit">Editar</a>
            <button type="button" id="empleadosModalToggle" class="list-action list-action--danger"></button>
            <button type="button" class="btn btn-secondary" data-close-modal>Cerrar</button>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(function () {
        var urlDatos = @json(route('socios.colaboradores.data'));
        var csrfToken = $('meta[name="csrf-token"]').attr('content');
        var $form = $('#filtrosEmpleados');
        var $inputBusqueda = $form.find('[name="search"]');
        var $inputRegistros = $form.find('[name="per_page"]');
        var $tableBody = $('#empleadosTableBody');
        var $cards = $('#empleadosCards');
        var $meta = $('#empleadosMeta');
        var $pagination = $('#empleadosPagination');
        var $modal = $('#empleadosActionModal');
        var $modalCopy = $('#empleadosActionModalCopy');
        var $modalShow = $('#empleadosModalShow');
        var $modalEdit = $('#empleadosModalEdit');
        var $modalToggle = $('#empleadosModalToggle');

        if (!$form.length) {
            return;
        }

        var paginaActual = 1;
        var estadoListado = window.utilidadesListado.crearEstadoHtml({
            entity: 'empleados',
            colSpan: 7
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
            var puedeCambiarEstado = esVerdadero(payload.canToggle);
            var estaActivo = esVerdadero(payload.active);

            $modalCopy.text('Selecciona que deseas hacer con "' + payload.name + '". Estado actual: ' + (estaActivo ? 'Activo' : 'Inactivo') + '.');
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
                return 'Si desactivas este empleado, dejara de estar disponible en las operaciones internas hasta que vuelvas a activarlo.';
            }

            return 'Si activas este empleado, volvera a estar disponible en el sistema.';
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
                window.notify('success', 'Empleados', response.message || 'Estado actualizado correctamente.');
                cargarEmpleados(paginaActual);
            }).fail(function (xhr) {
                var mensaje = xhr.responseJSON?.message || 'No se pudo actualizar el estado del empleado.';
                var tipo = xhr.status === 422 ? 'warning' : 'error';

                window.notify(tipo, 'Empleados', mensaje);
            });
        }

        function confirmarCambioEstado(payload) {
            var estaActivo = esVerdadero(payload.active);

            if (window.$wireui && typeof window.$wireui.confirmDialog === 'function') {
                window.$wireui.confirmDialog({
                    title: estaActivo ? 'Desactivar empleado' : 'Activar empleado',
                    description: obtenerMensajeConfirmacion(payload),
                    icon: estaActivo ? 'warning' : 'question',
                    accept: {
                        label: estaActivo ? 'Si, desactivar' : 'Si, activar',
                        color: estaActivo ? 'negative' : 'positive',
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

            if (window.confirm((estaActivo ? 'Desactivar empleado' : 'Activar empleado') + '\n\n' + obtenerMensajeConfirmacion(payload))) {
                ejecutarCambioEstado(payload);
            }
        }

        function cargarEmpleados(page) {
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
                    $meta.text('No hay empleados registrados.');
                    $tableBody.html(estadoListado.emptyRow);
                    $cards.html(estadoListado.emptyCards);
                    return;
                }

                $meta.text('Mostrando ' + paginationMeta.from + '-' + paginationMeta.to + ' de ' + paginationMeta.total + ' empleados');
            }).fail(function () {
                $tableBody.html(estadoListado.emptyRow);
                $cards.html(estadoListado.emptyCards);
                $pagination.html('');
                $meta.text('Error cargando empleados.');
                window.notify('error', 'Error', 'No se pudo obtener la lista de empleados.');
            });
        }

        window.utilidadesListado.vincularControlesFiltros({
            searchInput: $inputBusqueda,
            perPageInput: $inputRegistros,
            pagination: $pagination,
            getCurrentPage: function () {
                return paginaActual;
            },
            loadPage: cargarEmpleados,
        });

        $(document).on('click', '.js-cambiar-estado-empleado', function () {
            confirmarCambioEstado($(this).data());
        });

        $cards.on('click', '.js-empleado-card', function () {
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

        cargarEmpleados();
    });
</script>
@endsection

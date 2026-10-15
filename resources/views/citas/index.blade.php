@extends('layouts.main')

@section('title', 'Citas - xNova')
@section('page-title', 'Citas / Listado')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/listado.css') }}">
@endsection

@section('content')
@php
    $permissionCodes = $currentUserPermissionCodes ?? [];
    $canCreate = in_array('citas.crear', $permissionCodes, true);
    $canViewCalendar = in_array('citas.calendario.ver', $permissionCodes, true);
    $activeQuickView = collect($quickViews ?? [])->firstWhere('is_active', true) ?? ['locked_estado_id' => 0];
    $lockedEstadoId = (int) ($activeQuickView['locked_estado_id'] ?? 0);
@endphp
<div class="list-page">
    <x-card class="list-card">
        <x-slot name="header">
            <div class="list-card-header">
                <div>
                    <div class="list-card-title">Listado de citas</div>
                    <div class="list-card-subtitle">Administra citas reservadas y autorizadas, consulta su detalle y cambia su estado operativo.</div>
                </div>

                <div class="form-toolbar">
                    <a href="{{ route('inventario.productos.index') }}" class="btn btn-secondary">
                        <svg viewBox="0 0 24 24"><path d="M21 7.5 12 3 3 7.5v9L12 21l9-4.5v-9zM12 5.2l6.43 3.21L12 11.62 5.57 8.41 12 5.2zm-7 4.82 6 3v5.46l-6-3V10.02zm8 8.46v-5.46l6-3v5.46l-6 3z"/></svg>
                        Productos
                    </a>

                    <button type="button" class="btn btn-secondary" id="copyReservationLink" data-url="{{ $reservationUrl }}">
                        <svg viewBox="0 0 24 24"><path d="M3.9 12a5 5 0 0 1 5-5h3v2h-3a3 3 0 0 0 0 6h3v2h-3a5 5 0 0 1-5-5zm7.1 1h2v-2h-2v2zm4-6h-3v2h3a3 3 0 1 1 0 6h-3v2h3a5 5 0 1 0 0-10z"/></svg>
                        Copiar link
                    </button>

                    @if ($canViewCalendar)
                        <a href="{{ route('citas.calendario') }}" class="btn btn-secondary">
                            <svg viewBox="0 0 24 24"><path d="M19 4h-1V2h-2v2H8V2H6v2H5a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2zm0 15H5V10h14v9zm0-11H5V6h14v2z"/></svg>
                            Calendario
                        </a>
                    @endif

                    @if ($canCreate)
                        <a href="{{ route('citas.create') }}" class="btn btn-primary">
                            <svg viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
                            Nueva cita
                        </a>
                    @endif
                </div>
            </div>
        </x-slot>

        <div class="list-quick-views" id="citasQuickViews">
            @foreach ($quickViews as $quickView)
                <a
                    href="{{ $quickView['href'] }}"
                    class="list-quick-view js-citas-vista {{ $quickView['is_active'] ? 'is-active' : '' }}"
                    data-view="{{ $quickView['key'] }}"
                    data-fixed-status="{{ (int) ($quickView['locked_estado_id'] ?? 0) }}"
                >
                    <div class="list-quick-view__header">
                        <span class="list-quick-view__badge">{{ $quickView['badge'] }}</span>
                        <span class="list-quick-view__count">{{ $quickView['count'] }}</span>
                    </div>
                    <div class="list-quick-view__title">{{ $quickView['title'] }}</div>
                    <div class="list-quick-view__copy">{{ $quickView['description'] }}</div>
                </a>
            @endforeach
        </div>

        <form id="filtrosCitas" class="list-toolbar">
            <input type="hidden" name="vista" id="citas-vista" value="{{ $vistaActual }}">

            <div class="list-search">
                <x-input
                    id="citas-search"
                    name="search"
                    icon="magnifying-glass"
                    placeholder="Buscar cliente, empleado, sucursal o descripcion"
                />
            </div>

            <div class="list-per-page list-per-page--estado">
                <select id="citas-estado" name="estado_id" class="form-select" {{ $lockedEstadoId > 0 ? 'disabled' : '' }}>
                    <option value="" {{ $lockedEstadoId === 0 ? 'selected' : '' }}>Todos los estados</option>
                    @foreach ($estados as $estado)
                        <option value="{{ $estado->id }}" {{ $lockedEstadoId === (int) $estado->id ? 'selected' : '' }}>{{ $estado->nombre }}</option>
                    @endforeach
                </select>
            </div>

            <div class="list-per-page">
                <select id="citas-per-page" name="per_page" class="form-select">
                    <option value="5">5</option>
                    <option value="10" selected>10</option>
                    <option value="15">15</option>
                    <option value="25">25</option>
                </select>
            </div>
        </form>

        <div class="list-table-wrap">
            <div class="list-table-container">
                <table class="list-table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Cliente</th>
                            <th>Empleado</th>
                            <th>Sucursal</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="citasTableBody">
                        <tr>
                            <td colspan="6" class="list-loading-row">Cargando citas...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="list-cards-wrap">
            <div class="list-cards" id="citasCards">
                <div class="list-loading">Cargando citas...</div>
            </div>
        </div>

        <div class="list-feedback">
            <div class="list-feedback__meta" id="citasMeta">Cargando...</div>
            <div class="list-pagination" id="citasPagination"></div>
        </div>
    </x-card>
</div>

<div class="list-modal" id="citasActionModal" aria-hidden="true">
    <div class="list-modal__backdrop" data-close-modal></div>
    <div class="list-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="citasActionModalTitle">
        <div>
            <div class="list-modal__title" id="citasActionModalTitle">Acciones de la cita</div>
            <p class="list-modal__copy" id="citasActionModalCopy"></p>
        </div>

        <div class="list-modal__actions">
            <a href="#" id="citasModalShow" class="list-action list-action--info">Detalles</a>
            <a href="#" id="citasModalEdit" class="list-action list-action--edit">Editar</a>
            <button type="button" id="citasModalFinish" class="list-action list-action--success">Terminar</button>
            <button type="button" id="citasModalCancel" class="list-action list-action--danger">Cancelar</button>
            <button type="button" class="btn btn-secondary" data-close-modal>Cerrar</button>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(function () {
        var urlDatos = @json(route('citas.data'));
        var csrfToken = $('meta[name="csrf-token"]').attr('content');
        var $form = $('#filtrosCitas');
        var $inputBusqueda = $form.find('[name="search"]');
        var $inputEstado = $form.find('[name="estado_id"]');
        var $inputVista = $form.find('[name="vista"]');
        var $inputRegistros = $form.find('[name="per_page"]');
        var $tableBody = $('#citasTableBody');
        var $cards = $('#citasCards');
        var $meta = $('#citasMeta');
        var $pagination = $('#citasPagination');
        var $quickViews = $('#citasQuickViews');
        var $modal = $('#citasActionModal');
        var $modalCopy = $('#citasActionModalCopy');
        var $modalShow = $('#citasModalShow');
        var $modalEdit = $('#citasModalEdit');
        var $modalFinish = $('#citasModalFinish');
        var $modalCancel = $('#citasModalCancel');
        var $copyReservationLink = $('#copyReservationLink');

        if (!$form.length) {
            return;
        }

        var paginaActual = 1;
        var estadoListado = window.utilidadesListado.crearEstadoHtml({
            entity: 'citas',
            colSpan: 6
        });

        function esVerdadero(valor) {
            return Number(valor) === 1 || valor === true || valor === 'true';
        }

        function sincronizarVistaRapida($target, options) {
            if (!$target || !$target.length) {
                return;
            }

            var fixedStatus = Number($target.data('fixed-status')) || 0;

            $quickViews.find('.js-citas-vista').removeClass('is-active');
            $target.addClass('is-active');
            $inputVista.val($target.data('view') || 'todas');

            if (fixedStatus > 0) {
                $inputEstado.val(String(fixedStatus)).prop('disabled', true);
            } else {
                if ($inputEstado.prop('disabled')) {
                    $inputEstado.val('');
                }

                $inputEstado.prop('disabled', false);
            }

            if (!options || options.updateHistory !== false) {
                if (window.history && typeof window.history.replaceState === 'function') {
                    window.history.replaceState({}, '', $target.attr('href'));
                }
            }
        }

        function cerrarModalAcciones() {
            window.utilidadesFormulario.cerrarModal($modal);
        }

        function abrirModalAcciones(payload) {
            var puedeVer = esVerdadero(payload.canShow);
            var puedeEditar = esVerdadero(payload.canEdit);
            var puedeTerminar = esVerdadero(payload.canFinish);
            var puedeCancelar = esVerdadero(payload.canCancel);

            $modalCopy.text('Selecciona que deseas hacer con la cita de "' + payload.cliente + '" en estado ' + payload.estado + '.');
            $modalShow.attr('href', payload.showUrl || '#').toggle(puedeVer);
            $modalEdit.attr('href', payload.editUrl || '#').toggle(puedeEditar);
            $modalFinish.toggle(puedeTerminar).data('payload', {
                url: payload.finishUrl,
            });
            $modalCancel.toggle(puedeCancelar).data('payload', {
                url: payload.cancelUrl,
            });
            $modal.addClass('is-open').attr('aria-hidden', 'false');
        }

        function ejecutarCambioEstado(payload) {
            $.ajax({
                url: payload.url,
                method: 'POST',
                data: {
                    _token: csrfToken,
                    _method: 'PATCH',
                },
            }).done(function (response) {
                cerrarModalAcciones();
                window.notify('success', 'Citas', response.message || 'Estado actualizado correctamente.');
                cargarCitas(paginaActual);
            }).fail(function (xhr) {
                var mensaje = xhr.responseJSON?.message || 'No se pudo actualizar el estado de la cita.';
                var tipo = xhr.status === 422 ? 'warning' : 'error';

                window.notify(tipo, 'Citas', mensaje);
            });
        }

        function confirmarCambioEstado(payload, accion) {
            var titulo = accion === 'finish' ? 'Terminar cita' : 'Cancelar cita';
            var descripcion = accion === 'finish'
                ? 'Al terminar la cita ya no se podra volver a editar.'
                : 'Al cancelar la cita ya no se podra volver a editar.';

            if (window.$wireui && typeof window.$wireui.confirmDialog === 'function') {
                window.$wireui.confirmDialog({
                    title: titulo,
                    description: descripcion,
                    icon: 'warning',
                    accept: {
                        label: accion === 'finish' ? 'Si, terminar' : 'Si, cancelar',
                        color: accion === 'finish' ? 'positive' : 'negative',
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

            if (window.confirm(titulo + '\n\n' + descripcion)) {
                ejecutarCambioEstado(payload);
            }
        }

        function cargarCitas(page) {
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
                    vista: $inputVista.val(),
                    estado_id: $inputEstado.val(),
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
                    $meta.text('No hay citas registradas.');
                    $tableBody.html(estadoListado.emptyRow);
                    $cards.html(estadoListado.emptyCards);
                    return;
                }

                $meta.text('Mostrando ' + paginationMeta.from + '-' + paginationMeta.to + ' de ' + paginationMeta.total + ' citas');
            }).fail(function () {
                $tableBody.html(estadoListado.emptyRow);
                $cards.html(estadoListado.emptyCards);
                $pagination.html('');
                $meta.text('Error cargando citas.');
                window.notify('error', 'Error', 'No se pudo obtener la lista de citas.');
            });
        }

        window.utilidadesListado.vincularControlesFiltros({
            searchInput: $inputBusqueda,
            perPageInput: $inputRegistros,
            pagination: $pagination,
            getCurrentPage: function () {
                return paginaActual;
            },
            loadPage: cargarCitas,
        });

        $inputEstado.on('change', function () {
            cargarCitas(1);
        });

        $quickViews.on('click', '.js-citas-vista', function (event) {
            event.preventDefault();

            sincronizarVistaRapida($(this));
            cargarCitas(1);
        });

        $(document).on('click', '.js-cambiar-estado-cita', function () {
            var payload = $(this).data();
            confirmarCambioEstado(payload, payload.action);
        });

        $cards.on('click', '.js-cita-card', function () {
            abrirModalAcciones($(this).data());
        });

        $modalFinish.on('click', function () {
            var payload = $(this).data('payload');
            cerrarModalAcciones();

            if (payload) {
                confirmarCambioEstado(payload, 'finish');
            }
        });

        $modalCancel.on('click', function () {
            var payload = $(this).data('payload');
            cerrarModalAcciones();

            if (payload) {
                confirmarCambioEstado(payload, 'cancel');
            }
        });

        $modal.on('click', '[data-close-modal]', function () {
            cerrarModalAcciones();
        });

        $(document).on('keydown', function (event) {
            if (event.key === 'Escape') {
                cerrarModalAcciones();
            }
        });

        $copyReservationLink.on('click', function () {
            var url = $(this).data('url');

            if (!url) {
                window.notify('warning', 'Reservacion', 'No se pudo obtener el link de reservacion.');
                return;
            }

            if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
                navigator.clipboard.writeText(url).then(function () {
                    window.notify('success', 'Reservacion', 'Link de reservacion copiado.');
                }).catch(function () {
                    window.notify('warning', 'Reservacion', 'No se pudo copiar automaticamente. Abriendo el enlace en una nueva pestana.');
                    window.open(url, '_blank');
                });

                return;
            }

            window.open(url, '_blank');
        });

        sincronizarVistaRapida($quickViews.find('.js-citas-vista.is-active').first(), {
            updateHistory: false
        });
        cargarCitas();
    });
</script>
@endsection

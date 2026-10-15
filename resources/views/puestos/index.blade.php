
@extends('layouts.main')

@section('title', 'Puestos - xNova')
@section('page-title', 'Configuracion / Puestos')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/listado.css') }}">
@endsection


@section('content')
<div class="list-page">
    <x-card class="list-card">
        <x-slot name="header">
            <div class="list-card-header">
                <div>
                    <div class="list-card-title">Puestos</div>
                    <div class="list-card-subtitle">Administra puestos, su estado y sus acciones operativas.</div>
                </div>

                <a href="{{ route('puestos.create') }}" class="btn btn-primary">
                    <svg viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
                    Nuevo puesto
                </a>
            </div>
        </x-slot>

        <form id="puestosFilters" class="list-toolbar">
            <div class="list-search">
                <x-input
                    id="puestos-search"
                    name="search"
                    icon="magnifying-glass"
                    placeholder="Buscar puesto"
                />
            </div>

            <div class="list-per-page">
                <x-native-select id="puestos-per-page" name="per_page">
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
                            <th>Puesto</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="puestosTableBody">
                        <tr>
                            <td colspan="3" class="puestos-loading-row">Cargando puestos...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="list-cards-wrap">
            <div class="list-cards" id="puestosCards">
                <div class="list-loading">Cargando puestos...</div>
            </div>
        </div>

        <div class="list-feedback">
            <div class="list-feedback__meta" id="puestosMeta">Cargando...</div>
            <div class="list-pagination" id="puestosPagination"></div>
        </div>
    </x-card>
</div>

<div class="list-modal" id="puestoActionModal" aria-hidden="true">
    <div class="list-modal__backdrop" data-close-modal></div>
    <div class="list-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="puestoActionModalTitle">
        <div>
            <div class="list-modal__title" id="puestoActionModalTitle">Acciones del puesto</div>
            <p class="list-modal__copy" id="puestoActionModalCopy"></p>
        </div>

        <div class="list-modal__actions">
            <a href="#" id="puestoModalEdit" class="list-action list-action--edit">Editar</a>
            <button type="button" id="puestoModalToggle" class="list-action list-action--danger"></button>
            <button type="button" class="btn btn-secondary" data-close-modal>Cerrar</button>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(function () {
        var endpoint = "{{ route('puestos.data') }}";
        var csrfToken = $('meta[name="csrf-token"]').attr('content');
        var $form = $('#puestosFilters');
        var $searchInput = $form.find('[name="search"]');
        var $perPageInput = $form.find('[name="per_page"]');
        var $tableBody = $('#puestosTableBody');
        var $cards = $('#puestosCards');
        var $meta = $('#puestosMeta');
        var $pagination = $('#puestosPagination');
        var $actionModal = $('#puestoActionModal');
        var $modalCopy = $('#puestoActionModalCopy');
        var $modalEdit = $('#puestoModalEdit');
        var $modalToggle = $('#puestoModalToggle');

        if (!$form.length) {
            return;
        }

        var currentPage = 1;
        var debounceTimer = null;
        
        var listState = window.listUtils.createStateHtml({
            entity: 'puestos',
            colSpan: 3
        });

        function closeActionModal() {
            $actionModal.removeClass('is-open').attr('aria-hidden', 'true');
        }

        function openActionModal(payload) {
            $modalCopy.text('Selecciona qué deseas hacer con "' + payload.name + '". Estado actual: ' + (payload.active ? 'Activo' : 'Inactivo') + '.');
            $modalEdit.attr('href', payload.editUrl);
            
            $modalToggle
                .text(payload.active ? 'Desactivar' : 'Activar')
                .removeClass('list-action--danger list-action--success')
                .addClass(payload.active ? 'list-action--danger' : 'list-action--success')
                .data('payload', payload);

           

            $actionModal.addClass('is-open').attr('aria-hidden', 'false');
        }

        function statusConfirmationCopy(payload) {
            if (payload.active) {
                return 'Si desactivas este puesto, no se mostrará en las listas desplegables del sistema hasta que vuelvas a activarlo.';
            }

            return 'Si activas este puesto, volverá a mostrarse en las listas desplegables del sistema.';
        }

        function executeStatusChange(payload) {
            $.ajax({
                url: payload.url,
                method: 'POST',
                data: {
                    _token: csrfToken,
                    _method: payload.method,
                },
            }).done(function (response) {
                closeActionModal();
                window.notify('success', 'Puestos', response.message || 'Estado actualizado correctamente.');
                loadPuestos(currentPage);
            }).fail(function () {
                window.notify('error', 'Error', 'No se pudo actualizar el estado del puesto.');
            });
        }

        function confirmStatusChange(payload) {
            var title = payload.active ? '¿Desactivar puesto?' : '¿Activar puesto?';
            var acceptLabel = payload.active ? 'Sí, desactivar' : 'Sí, activar';
            var acceptColor = payload.active ? 'negative' : 'positive';

            if (window.$wireui && typeof window.$wireui.confirmDialog === 'function') {
                window.$wireui.confirmDialog({
                    title: title,
                    description: statusConfirmationCopy(payload),
                    icon: payload.active ? 'warning' : 'question',
                    accept: {
                        label: acceptLabel,
                        color: acceptColor,
                        execute: function () {
                            executeStatusChange(payload);
                        },
                    },
                    reject: {
                        label: 'Cancelar',
                    },
                });

                return;
            }

            if (window.confirm(title + '\n\n' + statusConfirmationCopy(payload))) {
                executeStatusChange(payload);
            }
        }

        function loadPuestos(page) {
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
                    $meta.text('No hay puestos registrados.');
                    $tableBody.html(listState.emptyRow);
                    $cards.html(listState.emptyCards);
                    return;
                }

                $meta.text('Mostrando ' + paginationMeta.from + '-' + paginationMeta.to + ' de ' + paginationMeta.total + ' puestos');
            }).fail(function () {
                $tableBody.html('<tr><td colspan="3" class="list-empty-row">No se pudo cargar el listado.</td></tr>');
                $cards.html('<div class="list-empty">No se pudo cargar el listado.</div>');
                $pagination.html('');
                $meta.text('Error cargando puestos.');
                window.notify('error', 'Error', 'No se pudo obtener la lista de puestos.');
            });
        }

        $searchInput.on('input', function () {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(function () {
                loadPuestos(1);
            }, 350);
        });

        $perPageInput.on('change', function () {
            loadPuestos(1);
        });

        $pagination.on('click', '[data-page]', function () {
            var nextPage = Number($(this).data('page'));

            if (!nextPage || nextPage === currentPage) {
                return;
            }

            loadPuestos(nextPage);
        });

        $(document).on('click', '.js-toggle-status', function () {
            confirmStatusChange($(this).data());
        });

        $cards.on('click', '.js-puesto-card', function () {
            openActionModal($(this).data());
        });

        $modalToggle.on('click', function () {
            closeActionModal();
            var payload = $(this).data('payload');

            if (!payload) {
                return;
            }

            confirmStatusChange(payload);
        });

        $actionModal.on('click', '[data-close-modal]', function () {
            closeActionModal();
        });

        $(document).on('keydown', function (event) {
            if (event.key === 'Escape') {
                closeActionModal();
            }
        });

        loadPuestos();
    });
</script>
@endsection

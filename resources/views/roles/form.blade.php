@extends('layouts.main')

@section('title', ($isEditing ? 'Editar Rol' : 'Nuevo Rol') . ' - xNova')
@section('page-title', $isEditing ? 'Seguridad / Roles / Editar' : 'Seguridad / Roles / Nuevo')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/crud.css') }}">
@endsection

@section('content')
<div class="form-page">
    <x-card class="form-card">
        <div class="form-header">
            <div>
                <h1 class="form-title">{{ $isEditing ? 'Editar rol' : 'Nuevo rol' }}</h1>
                <p class="form-subtitle">
                    {{ $isEditing
                        ? 'Actualiza el nombre del rol y ajusta los permisos que podra usar dentro del sistema. Antes de guardar se te pedira confirmacion.'
                        : 'Define el nombre del rol y marca los permisos que estaran disponibles para los usuarios que lo tengan asignado.'
                    }}
                </p>
            </div>

            <div class="form-toolbar">
                <a href="{{ route('roles.listado') }}" class="btn btn-secondary">
                    <svg viewBox="0 0 24 24"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20z"/></svg>
                    Volver
                </a>
            </div>
        </div>

        <div class="form-body">
            <section class="form-panel">
                <form id="rolesForm" class="form-grid" novalidate>
                    <div class="form-section">
                        <div>
                            <div class="form-section-title">Datos del rol</div>
                        </div>

                        <div class="form-field">
                            <x-input
                                id="nombre"
                                name="nombre"
                                label="Nombre del rol"
                                placeholder="Ejemplo: General"
                                icon="briefcase"
                                value="{{ $data->nombre }}"
                            />
                            <div class="form-error" data-error-for="nombre"></div>
                        </div>
                    </div>

                    <div class="form-section">
                        <div>
                            <div class="form-section-title">Permisos del rol</div>
                            <p class="form-section-copy">Marca los modulos, pantallas y acciones que estaran habilitados para este rol.</p>
                        </div>

                        <div class="form-field">
                            <label class="form-label">Permisos</label>
                            <div class="permission-tree">
                                @foreach ($permissionTree as $module)
                                    <article class="permission-module">
                                        <div class="permission-module-head">
                                            <label class="permission-check">
                                                <input type="checkbox" class="module-toggle" data-target="module-{{ $module['codigo'] }}">
                                                <span>{{ $module['nombre'] }}</span>
                                            </label>
                                        </div>

                                        <div class="permission-module-body" data-module-group="module-{{ $module['codigo'] }}">
                                            @foreach ($module['pantallas'] as $screen)
                                                <section class="permission-screen">
                                                    <div class="permission-screen-head">
                                                        <label class="permission-check">
                                                            <input type="checkbox" class="screen-toggle" data-target="screen-{{ $screen['codigo'] }}" @checked(in_array((int) ($screen['permiso_ver']['id'] ?? 0), $selectedPermissionIds, true))>
                                                            <span>{{ $screen['nombre'] }}</span>
                                                        </label>
                                                    </div>

                                                    <div class="permission-screen-body" data-screen-group="screen-{{ $screen['codigo'] }}">
                                                        @if ($screen['permiso_ver'])
                                                            <label class="permission-tag">
                                                                <input type="checkbox" name="permissions[]" value="{{ $screen['permiso_ver']['id'] }}" class="permission-box" @checked(in_array((int) $screen['permiso_ver']['id'], $selectedPermissionIds, true))>
                                                                <span>{{ $screen['permiso_ver']['nombre'] }}</span>
                                                            </label>
                                                        @endif

                                                        @if (! empty($screen['botones']))
                                                            <div class="permission-tags">
                                                                @foreach ($screen['botones'] as $button)
                                                                    @if ($button['permiso'])
                                                                        <label class="permission-tag">
                                                                            <input type="checkbox" name="permissions[]" value="{{ $button['permiso']['id'] }}" class="permission-box" @checked(in_array((int) $button['permiso']['id'], $selectedPermissionIds, true))>
                                                                            <span>{{ $button['permiso']['nombre'] }}</span>
                                                                        </label>
                                                                    @endif
                                                                @endforeach
                                                            </div>
                                                        @else
                                                            <div class="permission-empty">Esta pantalla no tiene botones adicionales.</div>
                                                        @endif
                                                    </div>
                                                </section>
                                            @endforeach
                                        </div>
                                    </article>
                                @endforeach
                            </div>
                            <div class="form-error" data-error-for="permissions"></div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <a href="{{ route('roles.listado') }}" class="btn btn-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-primary" id="rolesSaveButton">
                            <svg viewBox="0 0 24 24"><path d="M17 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V7l-4-4zM12 19a3 3 0 1 1 0-6 3 3 0 0 1 0 6zm3-10H5V5h10v4z"/></svg>
                            Guardar
                        </button>
                    </div>
                </form>
            </section>
        </div>
    </x-card>
</div>
@endsection

@section('scripts')
<script>
    $(function () {
        var esEdicion = @json($isEditing);
        var urlGuardar = @json($isEditing ? route('roles.update', $data) : route('roles.store'));
        var urlListado = @json(route('roles.listado'));
        var csrfToken = $('meta[name="csrf-token"]').attr('content');
        var $form = $('#rolesForm');
        var $botonGuardar = $('#rolesSaveButton');

        function sincronizarPantalla($contenedorPantalla) {
            var clavePantalla = $contenedorPantalla.data('screen-group');
            var total = $contenedorPantalla.find('.permission-box').length;
            var seleccionados = $contenedorPantalla.find('.permission-box:checked').length;

            $('.screen-toggle[data-target="' + clavePantalla + '"]').prop('checked', total > 0 && total === seleccionados);
        }

        function sincronizarModulo($contenedorModulo) {
            var claveModulo = $contenedorModulo.data('module-group');
            var total = $contenedorModulo.find('.permission-box').length;
            var seleccionados = $contenedorModulo.find('.permission-box:checked').length;

            $('.module-toggle[data-target="' + claveModulo + '"]').prop('checked', total > 0 && total === seleccionados);
        }

        function sincronizarTodo() {
            $('[data-screen-group]').each(function () {
                sincronizarPantalla($(this));
            });

            $('[data-module-group]').each(function () {
                sincronizarModulo($(this));
            });
        }

        function construirPayload() {
            var payload = $form.serializeArray();

            payload.push({ name: '_token', value: csrfToken });

            if (esEdicion) {
                payload.push({ name: '_method', value: 'PUT' });
            }

            return $.param(payload);
        }

        $('.module-toggle').on('change', function () {
            var objetivo = $(this).data('target');
            $('[data-module-group="' + objetivo + '"]').find('.permission-box, .screen-toggle').prop('checked', $(this).is(':checked'));
            sincronizarTodo();
        });

        $('.screen-toggle').on('change', function () {
            var objetivo = $(this).data('target');
            $('[data-screen-group="' + objetivo + '"]').find('.permission-box').prop('checked', $(this).is(':checked'));
            sincronizarTodo();
        });

        $('.permission-box').on('change', function () {
            var $contenedorPantalla = $(this).closest('[data-screen-group]');
            var claveModulo = $(this).closest('[data-module-group]').data('module-group');

            sincronizarPantalla($contenedorPantalla);
            sincronizarModulo($('[data-module-group="' + claveModulo + '"]'));
        });

        function enviarFormulario() {
            window.utilidadesFormulario.limpiarErrores($form);
            window.utilidadesFormulario.cambiarEstadoCarga(true, $botonGuardar);

            $.ajax({
                url: urlGuardar,
                method: 'POST',
                data: construirPayload(),
            }).done(function (response) {
                window.notify('success', 'Rol', response.message || 'Operacion completada.');
                window.location.href = urlListado;
            }).fail(function (xhr) {
                if (xhr.status === 422) {
                    window.utilidadesFormulario.mostrarErrores(xhr.responseJSON.errors || {}, $form);
                    window.notify('warning', 'Validacion', xhr.responseJSON?.message || 'Corrige los campos marcados para continuar.');
                    return;
                }

                window.notify('error', 'Error', 'No se pudo guardar el rol.');
            }).always(function () {
                window.utilidadesFormulario.cambiarEstadoCarga(false, $botonGuardar);
            });
        }

        $form.on('submit', function (event) {
            event.preventDefault();

            if (!esEdicion) {
                enviarFormulario();
                return;
            }

            if (window.$wireui && typeof window.$wireui.confirmDialog === 'function') {
                window.$wireui.confirmDialog({
                    title: 'Guardar cambios del rol',
                    description: 'Los permisos del rol se actualizaran dentro del sistema.',
                    icon: 'warning',
                    accept: {
                        label: 'Si, guardar',
                        color: 'info',
                        execute: enviarFormulario,
                    },
                    reject: {
                        label: 'Cancelar',
                    },
                });

                return;
            }

            if (window.confirm('Seguro que deseas guardar los cambios de este rol?')) {
                enviarFormulario();
            }
        });

        sincronizarTodo();
    });
</script>
@endsection

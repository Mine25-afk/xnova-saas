@extends('layouts.main')

@section('title', ($isEditing ? 'Editar Usuario' : 'Nuevo Usuario') . ' - xNova')
@section('page-title', $isEditing ? 'Seguridad / Usuarios / Editar' : 'Seguridad / Usuarios / Nuevo')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/crud.css') }}">
@endsection

@section('content')
<div class="form-page">
    <x-card class="form-card">
        <div class="form-header">
            <div>
                <h1 class="form-title">{{ $isEditing ? 'Editar usuario' : 'Nuevo usuario' }}</h1>
                <p class="form-subtitle">
                    {{ $isEditing
                        ? 'Actualiza el rol, la sucursal, el empleado y los datos principales de este usuario. Antes de guardar se te pedira confirmacion.'
                        : 'Completa la informacion principal del usuario y asignale un rol, una sucursal y un empleado para dejarlo listo dentro del sistema.'
                    }}
                </p>
            </div>

            <div class="form-toolbar">
                <a href="{{ route('usuarios.listado') }}" class="btn btn-secondary">
                    <svg viewBox="0 0 24 24"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20z"/></svg>
                    Volver
                </a>
            </div>
        </div>

        <div class="form-body">
            <section class="form-panel">
                <form id="usuarioForm" class="form-grid" novalidate>
                    <div class="form-section">
                        <div>
                            <div class="form-section-title">Datos del usuario</div>
                            <p class="form-section-copy">Aqui defines el nombre visible y el gmail que utilizara este usuario.</p>
                        </div>

                        <div class="form-grid-double">
                            <div class="form-field">
                                <x-input
                                    id="name"
                                    name="name"
                                    label="Nombre de usuario"
                                    placeholder="Ejemplo: Maria Lopez"
                                    icon="user"
                                    value="{{ $usuario->name }}"
                                />
                                <div class="form-error" data-error-for="name"></div>
                            </div>

                            <div class="form-field">
                                <x-input
                                    id="email"
                                    name="email"
                                    type="email"
                                    label="Gmail"
                                    placeholder="ejemplo@gmail.com"
                                    icon="at-symbol"
                                    value="{{ $usuario->email }}"
                                    :readonly="$isCompanyCreator"
                                />
                                @if ($isCompanyCreator)
                                    <div class="form-section-copy">El correo del usuario creador de la empresa no se puede modificar.</div>
                                @endif
                                <div class="form-error" data-error-for="email"></div>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <div>
                            <div class="form-section-title">Asignaciones</div>
                            <p class="form-section-copy">Selecciona el rol del usuario. La sucursal y el empleado son opcionales: deja en blanco si la empresa aun no maneja esos catalogos.</p>
                        </div>

                        <div class="form-grid">
                            <div class="form-field">
                                <x-native-select
                                    id="rol_id"
                                    name="rol_id"
                                    label="Asignar rol"
                                    :data-selected="(string) ($usuario->rol_id ?? '')"
                                >
                                    <option value="">{{ $isEditing ? 'Cargando roles...' : 'Selecciona un rol' }}</option>
                                    @if ($isEditing && $usuario->rol)
                                        <option value="{{ $usuario->rol->id }}" selected>{{ $usuario->rol->nombre }}</option>
                                    @endif
                                </x-native-select>
                                <div class="form-error" data-error-for="rol_id"></div>
                            </div>

                            <div class="form-field">
                                <x-native-select
                                    id="colaborador_dni"
                                    name="colaborador_dni"
                                    label="Asignar empleado (opcional)"
                                    :data-selected="(string) ($usuario->colaborador_dni ?? '')"
                                >
                                    <option value="">{{ $isEditing ? 'Cargando empleados...' : 'Sin empleado asignado' }}</option>
                                    @if ($isEditing && $usuario->colaborador?->socio)
                                        <option value="{{ $usuario->colaborador->dni }}" selected>
                                            {{ trim($usuario->colaborador->socio->nombres . ' ' . $usuario->colaborador->socio->apellidos) }}
                                        </option>
                                    @endif
                                </x-native-select>
                                <div class="form-error" data-error-for="colaborador_dni"></div>
                            </div>

                            <div class="form-field">
                                <x-native-select
                                    id="sucursal_Id"
                                    name="sucursal_Id"
                                    label="Asignar sucursal (opcional)"
                                    :data-selected="(string) ($usuario->sucursal_Id ?? '')"
                                >
                                    <option value="">{{ $isEditing ? 'Cargando sucursales...' : 'Sin sucursal asignada' }}</option>
                                    @if ($isEditing && $usuario->sucursal)
                                        <option value="{{ $usuario->sucursal->id }}" selected>{{ $usuario->sucursal->nombre }}</option>
                                    @endif
                                </x-native-select>
                                <div class="form-error" data-error-for="sucursal_Id"></div>
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <a href="{{ route('usuarios.listado') }}" class="btn btn-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-primary" id="usuarioSaveButton">
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
        var urlGuardar = @json($isEditing ? route('usuarios.update', $usuario) : route('usuarios.store'));
        var urlListado = @json(route('usuarios.listado'));
        var urlRoles = @json(route('listados.obtener', ['tipo' => 'roles']));
        var urlEmpleados = @json(route('listados.obtener', ['tipo' => 'empleados']));
        var urlSucursales = @json(route('listados.obtener', ['tipo' => 'sucursales']));
        var csrfToken = $('meta[name="csrf-token"]').attr('content');
        var $form = $('#usuarioForm');
        var $botonGuardar = $('#usuarioSaveButton');
        var $inputNombre = $('#name');
        var $inputEmail = $('#email');
        var $selectRol = $('#rol_id');
        var $selectEmpleado = $('#colaborador_dni');
        var $selectSucursal = $('#sucursal_Id');

        function cargarCatalogos() {
            $.when(
                window.utilidadesSelect.cargarOpciones({
                    select: $selectRol,
                    url: urlRoles,
                    placeholder: 'Selecciona un rol',
                    textoVacio: 'No hay roles disponibles',
                    selected: $selectRol.data('selected') || $selectRol.val() || ''
                }),
                window.utilidadesSelect.cargarOpciones({
                    select: $selectEmpleado,
                    url: urlEmpleados,
                    placeholder: 'Selecciona un empleado',
                    textoVacio: 'No hay empleados disponibles',
                    selected: $selectEmpleado.data('selected') || $selectEmpleado.val() || ''
                }),
                window.utilidadesSelect.cargarOpciones({
                    select: $selectSucursal,
                    url: urlSucursales,
                    placeholder: 'Selecciona una sucursal',
                    textoVacio: 'No hay sucursales disponibles',
                    selected: $selectSucursal.data('selected') || $selectSucursal.val() || ''
                })
            ).fail(function () {
                window.notify('warning', 'Listados', 'No se pudieron cargar todos los dropdowns del formulario.');
            });
        }

        function construirPayload() {
            return {
                _token: csrfToken,
                _method: esEdicion ? 'PUT' : 'POST',
                name: $.trim($inputNombre.val()),
                email: $.trim($inputEmail.val()),
                rol_id: $selectRol.val(),
                colaborador_dni: $selectEmpleado.val(),
                sucursal_Id: $selectSucursal.val(),
            };
        }

        function enviarFormulario() {
            window.utilidadesFormulario.limpiarErrores($form);
            window.utilidadesFormulario.cambiarEstadoCarga(true, $botonGuardar);

            $.ajax({
                url: urlGuardar,
                method: 'POST',
                data: construirPayload(),
            }).done(function (response) {
                window.notify('success', 'Usuarios', response.message || 'Operacion completada.');
                window.location.href = urlListado;
            }).fail(function (xhr) {
                if (xhr.status === 422) {
                    window.utilidadesFormulario.mostrarErrores(xhr.responseJSON.errors || {}, $form);
                    window.notify('warning', 'Validacion', xhr.responseJSON?.message || 'Corrige los campos marcados para continuar.');
                    return;
                }

                window.notify('error', 'Error', 'No se pudo guardar el usuario en este momento.');
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
                    title: 'Guardar cambios del usuario',
                    description: 'La informacion del usuario se actualizara en el sistema.',
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

            if (window.confirm('Seguro que deseas guardar los cambios de este usuario?')) {
                enviarFormulario();
            }
        });

        cargarCatalogos();
    });
</script>
@endsection

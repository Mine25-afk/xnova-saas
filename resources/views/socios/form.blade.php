@extends('layouts.main')

@php
    $pantalla = $isCollaborator ? 'Empleado' : 'Cliente';
    $urlListado = $isCollaborator ? route('socios.colaboradores.listado') : route('socios.clientes.listado');
    $defaultBirthDate = $data->fecha_nacimiento
        ? \Illuminate\Support\Carbon::parse($data->fecha_nacimiento)->format('Y-m-d')
        : now()->format('Y-m-d');
@endphp

@section('title', ($isEditing ? 'Editar ' : 'Nuevo ') . $pantalla . ' - xNova')
@section('page-title', $isEditing ? 'Socios / ' . $pantalla . ' / Editar' : 'Socios / ' . $pantalla . ' / Nuevo')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/crud.css') }}">
@endsection

@section('content')
<div class="form-page">
    <x-card class="form-card">
        <div class="form-header">
            <div>
                <h1 class="form-title">{{ $isEditing ? 'Editar ' . strtolower($pantalla) : 'Nuevo ' . strtolower($pantalla) }}</h1>
                <p class="form-subtitle">
                    {{ $isEditing
                        ? 'Actualiza la informacion principal de este ' . strtolower($pantalla) . '. Antes de guardar se te pedira confirmacion.'
                        : 'Completa la informacion para registrar un nuevo ' . strtolower($pantalla) . ' dentro del sistema.'
                    }}
                </p>
            </div>

            <div class="form-toolbar">
                <a href="{{ $urlListado }}" class="btn btn-secondary">
                    <svg viewBox="0 0 24 24"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20z"/></svg>
                    Volver
                </a>
            </div>
        </div>

        <div class="form-body">
            <section class="form-panel">
                <form id="socioForm" class="form-grid" novalidate>
                    <input type="hidden" id="tipo" name="tipo" value="{{ $tipo }}">

                    <div class="form-section">
                        <div>
                            <div class="form-section-title">Datos generales</div>
                            <p class="form-section-copy">Esta informacion es compartida tanto para clientes como para empleados.</p>
                            @if ($data->es_por_defecto)
                                <p class="form-section-copy">Este es el registro por defecto de la empresa y solo el dueno puede modificarlo.</p>
                            @endif
                        </div>

                        <div class="form-grid-double">
                            <div class="form-field">
                                <x-input
                                    id="dni"
                                    name="dni"
                                    label="Identidad"
                                    placeholder="Ejemplo: 0801199912345"
                                    icon="identification"
                                    value="{{ $data->dni }}"
                                    :readonly="$isEditing"
                                />
                                <div class="form-error" data-error-for="dni"></div>
                            </div>

                            <div class="form-field">
                                <x-input
                                    id="telefono"
                                    name="telefono"
                                    label="Telefono"
                                    placeholder="Ejemplo: 9999-9999"
                                    icon="phone"
                                    value="{{ $data->telefono }}"
                                />
                                <div class="form-error" data-error-for="telefono"></div>
                            </div>
                        </div>

                        <div class="form-field">
                            <x-input
                                id="correo"
                                name="correo"
                                type="email"
                                label="Correo electronico"
                                placeholder="Ejemplo: cliente@correo.com"
                                icon="envelope"
                                value="{{ $data->correo }}"
                            />
                            <div class="form-error" data-error-for="correo"></div>
                        </div>

                        <div class="form-grid-double">
                            <div class="form-field">
                                <x-input
                                    id="nombres"
                                    name="nombres"
                                    label="Nombres"
                                    placeholder="Ejemplo: Juan Carlos"
                                    icon="user"
                                    value="{{ $data->nombres }}"
                                />
                                <div class="form-error" data-error-for="nombres"></div>
                            </div>

                            <div class="form-field">
                                <x-input
                                    id="apellidos"
                                    name="apellidos"
                                    label="Apellidos"
                                    placeholder="Ejemplo: Perez Gomez"
                                    icon="user"
                                    value="{{ $data->apellidos }}"
                                />
                                <div class="form-error" data-error-for="apellidos"></div>
                            </div>
                        </div>

                        <div class="form-grid-double">
                            <div class="form-field">
                                <x-input
                                    id="fecha_nacimiento"
                                    name="fecha_nacimiento"
                                    type="date"
                                    label="Fecha de nacimiento"
                                    icon="calendar"
                                    value="{{ $defaultBirthDate }}"
                                />
                                <div class="form-error" data-error-for="fecha_nacimiento"></div>
                            </div>

                            <div class="form-field">
                                <x-native-select id="sexo" name="sexo" label="Sexo">
                                    <option value="M" @selected($data->sexo === 'M')>Masculino</option>
                                    <option value="F" @selected($data->sexo === 'F')>Femenino</option>
                                </x-native-select>
                                <div class="form-error" data-error-for="sexo"></div>
                            </div>
                        </div>
                    </div>

                    @if ($isCollaborator)
                        <div class="form-section">
                            <div>
                                <div class="form-section-title">Datos del empleado</div>
                                <p class="form-section-copy">Estos campos solo aplican cuando el registro forma parte del equipo interno.</p>
                            </div>

                            <div class="form-grid-double">
                                <div class="form-field">
                                    <x-native-select id="puesto_id" name="puesto_id" label="Puesto">
                                        <option value="">Selecciona un puesto</option>
                                        @foreach ($puestos as $puesto)
                                            <option value="{{ $puesto->id }}" @selected((string) optional($colaborador)->puesto_id === (string) $puesto->id)>
                                                {{ $puesto->nombre }}
                                            </option>
                                        @endforeach
                                    </x-native-select>
                                    <div class="form-error" data-error-for="puesto_id"></div>
                                </div>

                                <div class="form-field">
                                    <x-native-select id="sucursal_Id" name="sucursal_Id" label="Sucursal">
                                        <option value="">Selecciona una sucursal</option>
                                        @foreach ($sucursales as $sucursal)
                                            <option value="{{ $sucursal->id }}" @selected((string) optional($colaborador)->sucursal_Id === (string) $sucursal->id)>
                                                {{ $sucursal->nombre }}
                                            </option>
                                        @endforeach
                                    </x-native-select>
                                    <div class="form-error" data-error-for="sucursal_Id"></div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="form-actions">
                        <a href="{{ $urlListado }}" class="btn btn-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-primary" id="socioSaveButton">
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
        var esEmpleado = @json($isCollaborator);
        var tipo = @json($tipo);
        var urlGuardar = @json($isEditing ? route('socios.update', $data) : route('socios.store'));
        var urlListado = @json($urlListado);
        var csrfToken = $('meta[name="csrf-token"]').attr('content');
        var fechaNacimientoPorDefecto = @json($defaultBirthDate);
        var $form = $('#socioForm');
        var $botonGuardar = $('#socioSaveButton');
        var $inputIdentidad = $('#dni');
        var $inputNombres = $('#nombres');
        var $inputApellidos = $('#apellidos');
        var $inputTelefono = $('#telefono');
        var $inputCorreo = $('#correo');
        var $inputFechaNacimiento = $('#fecha_nacimiento');
        var $inputSexo = $('#sexo');
        var $inputPuesto = $('#puesto_id');
        var $inputSucursal = $('#sucursal_Id');

        function construirPayload() {
            return {
                _token: csrfToken,
                _method: esEdicion ? 'PUT' : 'POST',
                tipo: tipo,
                dni: $.trim($inputIdentidad.val()),
                nombres: $.trim($inputNombres.val()),
                apellidos: $.trim($inputApellidos.val()),
                telefono: $.trim($inputTelefono.val()),
                correo: $.trim($inputCorreo.val()),
                fecha_nacimiento: $inputFechaNacimiento.val(),
                sexo: $inputSexo.val(),
                puesto_id: esEmpleado ? ($inputPuesto.val() || '') : '',
                sucursal_Id: esEmpleado ? ($inputSucursal.val() || '') : '',
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
                window.notify('success', @json($pantalla), response.message || 'Operacion completada.');
                window.location.href = urlListado;
            }).fail(function (xhr) {
                if (xhr.status === 422) {
                    window.utilidadesFormulario.mostrarErrores(xhr.responseJSON.errors || {}, $form);
                    window.notify('warning', 'Validacion', xhr.responseJSON.message || 'Corrige los campos marcados para continuar.');
                    return;
                }

                window.notify('error', 'Error', 'No se pudo guardar el registro en este momento.');
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
                    title: 'Guardar cambios',
                    description: 'La informacion se actualizara en el sistema.',
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

            if (window.confirm('Seguro que deseas guardar los cambios?')) {
                enviarFormulario();
            }
        });
    });
</script>
@endsection

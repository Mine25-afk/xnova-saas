@extends('layouts.main')

@section('title', ($isEditing ? 'Editar Cita' : 'Nueva Cita') . ' - xNova')
@section('page-title', $isEditing ? 'Citas / Editar' : 'Citas / Nuevo')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/crud.css') }}">
    <style>
        .availability-box {
            border: 1px solid rgba(148, 163, 184, 0.35);
            border-radius: 16px;
            background: #f8fafc;
            padding: 14px;
            display: grid;
            gap: 10px;
        }

        .availability-hint {
            margin: 0;
            font-size: 0.9rem;
            color: #475569;
            line-height: 1.55;
        }

        .availability-slots {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .availability-slot {
            border: 1px solid rgba(15, 118, 110, 0.25);
            background: #ffffff;
            color: #0f766e;
            border-radius: 999px;
            font-size: 0.82rem;
            font-weight: 700;
            padding: 8px 12px;
            cursor: pointer;
            transition: background 0.2s ease, color 0.2s ease;
        }

        .availability-slot:hover {
            background: #0f766e;
            color: #ffffff;
        }
    </style>
@endsection

@section('content')
@php
    $fechaMinima = now()->toDateString();
@endphp
<div class="form-page">
    <x-card class="form-card">
        <div class="form-header">
            <div>
                <h1 class="form-title">{{ $isEditing ? 'Editar cita' : 'Nueva cita' }}</h1>
                <p class="form-subtitle">
                    {{ $isEditing
                        ? 'Actualiza una cita reservada o autorizada. Si queda fuera del horario laboral configurado se mostrara una advertencia antes de guardar.'
                        : 'Registra una nueva cita autorizada y valida su horario contra la sucursal si existe un horario asignado.'
                    }}
                </p>
            </div>

            <div class="form-toolbar">
                <a href="{{ route('citas.listado') }}" class="btn btn-secondary">
                    <svg viewBox="0 0 24 24"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20z"/></svg>
                    Volver
                </a>
            </div>
        </div>

        <div class="form-body">
            <section class="form-panel">
                <form id="citaForm" class="form-grid" novalidate>
                    <div class="form-section">
                        <div>
                            <div class="form-section-title">Estado actual</div>
                            <p class="form-section-copy">Las citas creadas por el equipo se guardan como autorizadas. Solo las citas reservadas o autorizadas se pueden editar.</p>
                        </div>

                        @php
                            $estadoClase = (int) $cita->estado_id === \App\Models\Cita::ESTADO_TERMINADA_ID
                                ? 'is-finished'
                                : ((int) $cita->estado_id === \App\Models\Cita::ESTADO_CANCELADA_ID ? 'is-cancelled' : 'is-reserved');
                            $estadoTexto = $isEditing ? ($cita->estado?->nombre ?? 'Autorizado') : 'Autorizado';
                        @endphp
                        <span class="form-status {{ $estadoClase }}">{{ $estadoTexto }}</span>
                    </div>

                    <div class="form-section">
                        <div>
                            <div class="form-section-title">Datos principales</div>
                            <p class="form-section-copy">Selecciona el cliente, el empleado responsable y la sucursal donde se atendera la cita.</p>
                        </div>

                        <div class="form-grid-double">
                            <div class="form-field">
                                <x-native-select
                                    id="cliente_dni"
                                    name="cliente_dni"
                                    label="Cliente"
                                    :data-selected="(string) ($cita->cliente_dni ?? '')"
                                >
                                    <option value="">{{ $isEditing ? 'Cargando clientes...' : 'Selecciona un cliente' }}</option>
                                    @if ($cita->cliente)
                                        <option value="{{ $cita->cliente->dni }}" selected>{{ trim($cita->cliente->nombres . ' ' . $cita->cliente->apellidos) }}</option>
                                    @endif
                                </x-native-select>
                                <div class="form-error" data-error-for="cliente_dni"></div>
                            </div>

                            <div class="form-field">
                                <x-native-select
                                    id="colaborador_dni"
                                    name="colaborador_dni"
                                    label="Empleado"
                                    :data-selected="(string) ($cita->colaborador_dni ?? '')"
                                >
                                    <option value="">{{ $isEditing ? 'Cargando empleados...' : 'Selecciona un empleado' }}</option>
                                    @if ($cita->colaborador?->socio)
                                        <option value="{{ $cita->colaborador->dni }}" selected>{{ trim($cita->colaborador->socio->nombres . ' ' . $cita->colaborador->socio->apellidos) }}</option>
                                    @endif
                                </x-native-select>
                                <div class="form-error" data-error-for="colaborador_dni"></div>
                            </div>

                            <div class="form-field">
                                <x-native-select
                                    id="sucursal_Id"
                                    name="sucursal_Id"
                                    label="Sucursal"
                                    :data-selected="(string) ($cita->sucursal_Id ?? '')"
                                >
                                    <option value="">{{ $isEditing ? 'Cargando sucursales...' : 'Selecciona una sucursal' }}</option>
                                    @if ($cita->sucursal)
                                        <option value="{{ $cita->sucursal->id }}" selected>{{ $cita->sucursal->nombre }}</option>
                                    @endif
                                </x-native-select>
                                <div class="form-error" data-error-for="sucursal_Id"></div>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <div>
                            <div class="form-section-title">Programacion</div>
                            <p class="form-section-copy">Define la fecha y el rango de tiempo que tendra la cita.</p>
                        </div>

                        <div class="form-grid-double">
                            <div class="form-field">
                                <x-input
                                    id="fecha"
                                    name="fecha"
                                    type="date"
                                    label="Fecha"
                                    min="{{ $fechaMinima }}"
                                    value="{{ old('fecha', optional($cita->fecha)->format('Y-m-d') ?? now()->toDateString()) }}"
                                />
                                <div class="form-error" data-error-for="fecha"></div>
                            </div>

                            <div class="form-field">
                                <x-input
                                    id="hora_inicio"
                                    name="hora_inicio"
                                    type="time"
                                    label="Hora de inicio"
                                    value="{{ old('hora_inicio', substr((string) $cita->hora_inicio, 0, 5) ?: '09:00') }}"
                                />
                                <div class="form-error" data-error-for="hora_inicio"></div>
                            </div>

                            <div class="form-field">
                                <x-input
                                    id="hora_salida"
                                    name="hora_salida"
                                    type="time"
                                    label="Hora de salida"
                                    value="{{ old('hora_salida', substr((string) $cita->hora_salida, 0, 5) ?: '10:00') }}"
                                />
                                <div class="form-error" data-error-for="hora_salida"></div>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <div>
                            <div class="form-section-title">Huecos libres del empleado</div>
                            <p class="form-section-copy">Consulta rapidamente horarios disponibles segun la sucursal y fecha seleccionada. Presiona un hueco para aplicar esa hora.</p>
                        </div>

                        <div class="availability-box">
                            <p class="availability-hint" id="availabilityHint">Selecciona empleado, sucursal y fecha para calcular huecos libres.</p>
                            <div class="availability-slots" id="availabilitySlots"></div>
                        </div>
                    </div>

                    <div class="form-section">
                        <div>
                            <div class="form-section-title">Atencion</div>
                            <p class="form-section-copy">Agrega el producto relacionado, la descripcion principal y un comentario opcional para el equipo.</p>
                        </div>

                        <div class="form-field">
                            <x-native-select
                                id="producto_id"
                                name="producto_id"
                                label="Producto"
                                :data-selected="(string) ($cita->producto_id ?? '')"
                            >
                                <option value="">Selecciona un producto</option>
                                @if ($cita->producto)
                                    <option value="{{ $cita->producto->id }}" selected>{{ $cita->producto->nombre }} - {{ number_format((float) $cita->producto->precio, 2) }}</option>
                                @endif
                            </x-native-select>
                            <div class="form-error" data-error-for="producto_id"></div>
                        </div>

                        <div class="form-field">
                            <x-input
                                id="descripcion"
                                name="descripcion"
                                label="Descripcion"
                                placeholder="Ejemplo: Consulta inicial"
                                icon="document-text"
                                value="{{ $cita->descripcion }}"
                            />
                            <div class="form-error" data-error-for="descripcion"></div>
                        </div>

                        <div class="form-field">
                            <label class="form-label" for="comentario">Comentario</label>
                            <textarea id="comentario" name="comentario" class="form-input" rows="4" placeholder="Comentario opcional">{{ $cita->comentario }}</textarea>
                            <div class="form-error" data-error-for="comentario"></div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <a href="{{ route('citas.listado') }}" class="btn btn-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-primary" id="citaSaveButton">
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
        var fechaMinima = @json($fechaMinima);
        var urlGuardar = @json($isEditing ? route('citas.update', $cita) : route('citas.store'));
        var urlListado = @json(route('citas.listado'));
        var urlHuecosLibres = @json(route('citas.free-slots'));
        var urlClientes = @json(route('listados.obtener', ['tipo' => 'clientes-activos']));
        var urlColaboradores = @json(route('listados.obtener', ['tipo' => 'colaboradores-activos']));
        var urlSucursales = @json(route('listados.obtener', ['tipo' => 'sucursales']));
        var urlProductos = @json(route('listados.obtener', ['tipo' => 'productos']));
        var csrfToken = $('meta[name="csrf-token"]').attr('content');
        var $form = $('#citaForm');
        var $botonGuardar = $('#citaSaveButton');
        var $selectCliente = $('#cliente_dni');
        var $selectColaborador = $('#colaborador_dni');
        var $selectSucursal = $('#sucursal_Id');
        var $selectProducto = $('#producto_id');
        var $inputFecha = $('#fecha');
        var $inputHoraInicio = $('#hora_inicio');
        var $inputHoraSalida = $('#hora_salida');
        var $inputDescripcion = $('#descripcion');
        var $inputComentario = $('#comentario');
        var $availabilityHint = $('#availabilityHint');
        var $availabilitySlots = $('#availabilitySlots');

        function cargarCatalogos() {
            $.when(
                window.utilidadesSelect.cargarOpciones({
                    select: $selectCliente,
                    url: urlClientes,
                    placeholder: 'Selecciona un cliente',
                    textoVacio: 'No hay clientes disponibles',
                    selected: $selectCliente.data('selected') || $selectCliente.val() || ''
                }),
                window.utilidadesSelect.cargarOpciones({
                    select: $selectColaborador,
                    url: urlColaboradores,
                    placeholder: 'Selecciona un empleado',
                    textoVacio: 'No hay empleados disponibles',
                    selected: $selectColaborador.data('selected') || $selectColaborador.val() || ''
                }),
                window.utilidadesSelect.cargarOpciones({
                    select: $selectSucursal,
                    url: urlSucursales,
                    placeholder: 'Selecciona una sucursal',
                    textoVacio: 'No hay sucursales disponibles',
                    selected: $selectSucursal.data('selected') || $selectSucursal.val() || ''
                }),
                window.utilidadesSelect.cargarOpciones({
                    select: $selectProducto,
                    url: urlProductos,
                    placeholder: 'Selecciona un producto',
                    textoVacio: 'No hay productos disponibles',
                    selected: $selectProducto.data('selected') || $selectProducto.val() || ''
                })
            ).done(function () {
                cargarHuecosLibres();
            }).fail(function () {
                window.notify('warning', 'Listados', 'No se pudieron cargar todos los dropdowns del formulario.');
            });
        }

        function construirPayload(confirmarFueraHorario) {
            return {
                _token: csrfToken,
                _method: esEdicion ? 'PUT' : 'POST',
                cliente_dni: $selectCliente.val(),
                colaborador_dni: $selectColaborador.val(),
                sucursal_Id: $selectSucursal.val(),
                producto_id: $selectProducto.val(),
                fecha: $.trim($inputFecha.val()),
                hora_inicio: $.trim($inputHoraInicio.val()),
                hora_salida: $.trim($inputHoraSalida.val()),
                descripcion: $.trim($inputDescripcion.val()),
                comentario: $.trim($inputComentario.val()),
                confirmar_fuera_horario: confirmarFueraHorario ? 1 : 0,
            };
        }

        function calcularDuracionActual() {
            var inicio = $.trim($inputHoraInicio.val() || '');
            var salida = $.trim($inputHoraSalida.val() || '');

            if (inicio.length < 4 || salida.length < 4) {
                return 60;
            }

            var inicioPartes = inicio.split(':');
            var salidaPartes = salida.split(':');

            var minutosInicio = (parseInt(inicioPartes[0], 10) * 60) + parseInt(inicioPartes[1], 10);
            var minutosSalida = (parseInt(salidaPartes[0], 10) * 60) + parseInt(salidaPartes[1], 10);

            if (!Number.isFinite(minutosInicio) || !Number.isFinite(minutosSalida) || minutosSalida <= minutosInicio) {
                return 60;
            }

            return Math.min(240, Math.max(15, minutosSalida - minutosInicio));
        }

        function pintarHuecos(slots) {
            $availabilitySlots.empty();

            if (!Array.isArray(slots) || slots.length === 0) {
                return;
            }

            slots.forEach(function (slot) {
                var texto = (slot.inicio || '') + ' - ' + (slot.salida || '');
                var $slot = $('<button type="button" class="availability-slot"></button>')
                    .text(texto)
                    .attr('data-inicio', slot.inicio || '')
                    .attr('data-salida', slot.salida || '');

                $availabilitySlots.append($slot);
            });
        }

        function cargarHuecosLibres() {
            var colaborador = $selectColaborador.val();
            var sucursal = $selectSucursal.val();
            var fecha = $.trim($inputFecha.val());

            if (!colaborador || !sucursal || !fecha) {
                $availabilityHint.text('Selecciona empleado, sucursal y fecha para calcular huecos libres.');
                $availabilitySlots.empty();
                return;
            }

            $availabilityHint.text('Consultando huecos libres...');
            $availabilitySlots.empty();

            $.ajax({
                url: urlHuecosLibres,
                method: 'GET',
                data: {
                    colaborador_dni: colaborador,
                    sucursal_Id: sucursal,
                    fecha: fecha,
                    duracion: calcularDuracionActual(),
                },
            }).done(function (response) {
                var slots = response.slots || [];
                var mensaje = response.message || '';

                pintarHuecos(slots);

                if (slots.length > 0) {
                    $availabilityHint.text('Selecciona un horario sugerido para aplicarlo al formulario.');
                    return;
                }

                $availabilityHint.text(mensaje || 'No hay huecos libres disponibles para los filtros actuales.');
            }).fail(function () {
                $availabilityHint.text('No se pudieron calcular los huecos libres en este momento.');
                $availabilitySlots.empty();
            });
        }

        function pedirConfirmacionHorario(callback, mensaje) {
            if (window.$wireui && typeof window.$wireui.confirmDialog === 'function') {
                window.$wireui.confirmDialog({
                    title: 'Cita fuera de horario laboral',
                    description: mensaje,
                    icon: 'warning',
                    accept: {
                        label: 'Si, guardar',
                        color: 'warning',
                        execute: callback,
                    },
                    reject: {
                        label: 'Cancelar',
                    },
                });

                return;
            }

            if (window.confirm(mensaje)) {
                callback();
            }
        }

        function validarFechaMinima() {
            var fechaSeleccionada = $.trim($inputFecha.val());

            if (fechaSeleccionada !== '' && fechaSeleccionada < fechaMinima) {
                window.utilidadesFormulario.mostrarErrores({
                    fecha: ['La fecha no puede ser menor a la fecha actual.'],
                }, $form);
                window.notify('warning', 'Validacion', 'La fecha no puede ser menor a la fecha actual.');

                return false;
            }

            return true;
        }

        function enviarFormulario(confirmarFueraHorario) {
            confirmarFueraHorario = confirmarFueraHorario || false;

            window.utilidadesFormulario.limpiarErrores($form);

            if (!validarFechaMinima()) {
                return;
            }

            window.utilidadesFormulario.cambiarEstadoCarga(true, $botonGuardar);

            $.ajax({
                url: urlGuardar,
                method: 'POST',
                data: construirPayload(confirmarFueraHorario),
            }).done(function (response) {
                window.notify('success', 'Citas', response.message || 'Operacion completada.');
                window.location.href = urlListado;
            }).fail(function (xhr) {
                if (xhr.status === 422 && xhr.responseJSON?.requiere_confirmacion_fuera_horario) {
                    window.utilidadesFormulario.cambiarEstadoCarga(false, $botonGuardar);
                    pedirConfirmacionHorario(function () {
                        enviarFormulario(true);
                    }, xhr.responseJSON?.message || 'Esta cita queda fuera del horario laboral configurado para la sucursal. Estas seguro de agregarla?');
                    return;
                }

                if (xhr.status === 422) {
                    window.utilidadesFormulario.mostrarErrores(xhr.responseJSON.errors || {}, $form);
                    window.notify('warning', 'Validacion', xhr.responseJSON?.message || 'Corrige los campos marcados para continuar.');
                    return;
                }

                window.notify('error', 'Error', 'No se pudo guardar la cita en este momento.');
            }).always(function () {
                window.utilidadesFormulario.cambiarEstadoCarga(false, $botonGuardar);
            });
        }

        $form.on('submit', function (event) {
            event.preventDefault();

            if (!esEdicion) {
                enviarFormulario(false);
                return;
            }

            if (window.$wireui && typeof window.$wireui.confirmDialog === 'function') {
                window.$wireui.confirmDialog({
                    title: 'Guardar cambios de la cita',
                    description: 'La informacion de la cita se actualizara en el sistema.',
                    icon: 'warning',
                    accept: {
                        label: 'Si, guardar',
                        color: 'info',
                        execute: function () {
                            enviarFormulario(false);
                        },
                    },
                    reject: {
                        label: 'Cancelar',
                    },
                });

                return;
            }

            if (window.confirm('Seguro que deseas guardar los cambios de esta cita?')) {
                enviarFormulario(false);
            }
        });

        $availabilitySlots.on('click', '.availability-slot', function () {
            var inicio = $(this).data('inicio');
            var salida = $(this).data('salida');

            if (inicio) {
                $inputHoraInicio.val(inicio);
            }

            if (salida) {
                $inputHoraSalida.val(salida);
            }
        });

        $selectColaborador.on('change', cargarHuecosLibres);
        $selectSucursal.on('change', cargarHuecosLibres);
        $inputFecha.on('change', cargarHuecosLibres);
        $inputHoraInicio.on('change', cargarHuecosLibres);
        $inputHoraSalida.on('change', cargarHuecosLibres);

        cargarCatalogos();
    });
</script>
@endsection

@extends('layouts.main')

@section('title', ($isEditing ? 'Editar Sucursal' : 'Nueva Sucursal') . ' - xNova')
@section('page-title', $isEditing ? 'Configuracion / Sucursales / Editar' : 'Configuracion / Sucursales / Nuevo')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/crud.css') }}">
    <style>
        .schedule-rows {
            display: grid;
            gap: 14px;
        }

        .schedule-row {
            border: 1px solid rgba(226, 232, 240, 0.95);
            border-radius: 16px;
            background: #fff;
            padding: 14px;
            display: grid;
            gap: 12px;
        }

        .schedule-row.is-off {
            opacity: 0.75;
            background: #f8fafc;
        }

        .schedule-row__head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
        }

        .schedule-row__day {
            font-size: 0.92rem;
            font-weight: 700;
            color: var(--text-primary);
        }

        .schedule-row__hint {
            font-size: 0.8rem;
            color: var(--text-secondary);
            margin-top: 2px;
        }

        .schedule-toggle {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.82rem;
            font-weight: 600;
            color: var(--text-primary);
            cursor: pointer;
        }

        .schedule-toggle input {
            width: 18px;
            height: 18px;
            accent-color: var(--primary);
        }

        .schedule-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 12px;
        }

        @media (max-width: 960px) {
            .schedule-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 620px) {
            .schedule-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endsection

@section('content')
<div class="form-page">
    <x-card class="form-card">
        <div class="form-header">
            <div>
                <h1 class="form-title">{{ $isEditing ? 'Editar sucursal' : 'Nueva sucursal' }}</h1>
                <p class="form-subtitle">
                    {{ $isEditing
                        ? 'Actualiza la informacion principal de esta sucursal. Antes de guardar se te pedira confirmacion.'
                        : 'Completa el nombre principal de la sucursal para dejarla lista dentro del sistema.'
                    }}
                </p>
            </div>

            <div class="form-toolbar">
                <a href="{{ route('sucursales.listado') }}" class="btn btn-secondary">
                    <svg viewBox="0 0 24 24"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20z"/></svg>
                    Volver
                </a>
            </div>
        </div>

        <div class="form-body">
            <section class="form-panel">
                <form id="sucursalForm" class="form-grid" novalidate>
                    <div class="form-section">
                        <div>
                            <div class="form-section-title">Datos de la sucursal</div>
                        </div>

                        <div class="form-field">
                            <x-input
                                id="nombre"
                                name="nombre"
                                label="Nombre de la sucursal"
                                placeholder="Ejemplo: Sucursal Centro"
                                icon="briefcase"
                                value="{{ $sucursal->nombre }}"
                            />
                            <div class="form-error" data-error-for="nombre"></div>
                        </div>
                    </div>

                    <div class="form-section">
                        <div>
                            <div class="form-section-title">Horario semanal</div>
                            <p class="form-section-copy">Configura horario laboral por dia. Para dias libres, desactiva el interruptor.</p>
                        </div>

                        <div class="schedule-rows">
                            @foreach ($scheduleRows as $scheduleRow)
                                @php
                                    $day = (int) $scheduleRow['dia'];
                                    $isEnabled = (bool) ($scheduleRow['enabled'] ?? false);
                                @endphp

                                <article class="schedule-row {{ $isEnabled ? '' : 'is-off' }}" data-schedule-day-row="{{ $day }}">
                                    <div class="schedule-row__head">
                                        <div>
                                            <div class="schedule-row__day">{{ $scheduleRow['nombre'] }}</div>
                                            <div class="schedule-row__hint">{{ $isEnabled ? 'Horario activo' : 'Dia libre' }}</div>
                                        </div>

                                        <label class="schedule-toggle">
                                            <input
                                                type="checkbox"
                                                value="1"
                                                data-schedule-enabled
                                                name="schedule[{{ $day }}][enabled]"
                                                @checked($isEnabled)
                                            >
                                            <span>Habilitado</span>
                                        </label>
                                    </div>

                                    <div class="schedule-grid">
                                        <div class="form-field">
                                            <x-input
                                                id="schedule_{{ $day }}_hora_inicio"
                                                name="schedule[{{ $day }}][hora_inicio]"
                                                type="time"
                                                label="Hora inicio"
                                                value="{{ $scheduleRow['hora_inicio'] }}"
                                            />
                                            <div class="form-error" data-error-for="schedule.{{ $day }}.hora_inicio"></div>
                                        </div>

                                        <div class="form-field">
                                            <x-input
                                                id="schedule_{{ $day }}_hora_salida"
                                                name="schedule[{{ $day }}][hora_salida]"
                                                type="time"
                                                label="Hora salida"
                                                value="{{ $scheduleRow['hora_salida'] }}"
                                            />
                                            <div class="form-error" data-error-for="schedule.{{ $day }}.hora_salida"></div>
                                        </div>

                                        <div class="form-field">
                                            <x-input
                                                id="schedule_{{ $day }}_receso_incio"
                                                name="schedule[{{ $day }}][receso_incio]"
                                                type="time"
                                                label="Receso inicio"
                                                value="{{ $scheduleRow['receso_incio'] }}"
                                            />
                                            <div class="form-error" data-error-for="schedule.{{ $day }}.receso_incio"></div>
                                        </div>

                                        <div class="form-field">
                                            <x-input
                                                id="schedule_{{ $day }}_receso_salida"
                                                name="schedule[{{ $day }}][receso_salida]"
                                                type="time"
                                                label="Receso fin"
                                                value="{{ $scheduleRow['receso_salida'] }}"
                                            />
                                            <div class="form-error" data-error-for="schedule.{{ $day }}.receso_salida"></div>
                                        </div>
                                    </div>

                                    <div class="form-error" data-error-for="schedule.{{ $day }}.enabled"></div>
                                </article>
                            @endforeach

                            <div class="form-error" data-error-for="schedule"></div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <a href="{{ route('sucursales.listado') }}" class="btn btn-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-primary" id="sucursalSaveButton">
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
        var urlGuardar = @json($isEditing ? route('sucursales.update', $sucursal) : route('sucursales.store'));
        var urlListado = @json(route('sucursales.listado'));
        var csrfToken = $('meta[name="csrf-token"]').attr('content');
        var $form = $('#sucursalForm');
        var $botonGuardar = $('#sucursalSaveButton');
        var $inputNombre = $('#nombre');
        var $scheduleRows = $('[data-schedule-day-row]');

        function syncScheduleRowState($row) {
            var enabled = $row.find('[data-schedule-enabled]').is(':checked');
            var $timeInputs = $row.find('input[type="time"]');

            $row.toggleClass('is-off', !enabled);
            $timeInputs.prop('disabled', !enabled);
        }

        function readSchedulePayload() {
            var schedule = {};

            $scheduleRows.each(function () {
                var $row = $(this);
                var day = String($row.data('schedule-day-row'));
                var enabled = $row.find('[data-schedule-enabled]').is(':checked');

                schedule[day] = {
                    enabled: enabled ? 1 : 0,
                    hora_inicio: enabled ? $.trim($row.find('[name="schedule[' + day + '][hora_inicio]"]').val()) : '',
                    hora_salida: enabled ? $.trim($row.find('[name="schedule[' + day + '][hora_salida]"]').val()) : '',
                    receso_incio: enabled ? $.trim($row.find('[name="schedule[' + day + '][receso_incio]"]').val()) : '',
                    receso_salida: enabled ? $.trim($row.find('[name="schedule[' + day + '][receso_salida]"]').val()) : '',
                };
            });

            return schedule;
        }

        function construirPayload() {
            return {
                _token: csrfToken,
                _method: esEdicion ? 'PUT' : 'POST',
                nombre: $.trim($inputNombre.val()),
                schedule: readSchedulePayload(),
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
                window.notify('success', 'Sucursales', response.message || 'Operacion completada.');
                window.location.href = urlListado;
            }).fail(function (xhr) {
                if (xhr.status === 422) {
                    window.utilidadesFormulario.mostrarErrores(xhr.responseJSON.errors || {}, $form);
                    window.notify('warning', 'Validacion', xhr.responseJSON?.message || 'Corrige los campos marcados para continuar.');
                    return;
                }

                window.notify('error', 'Error', 'No se pudo guardar la sucursal en este momento.');
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
                    title: 'Guardar cambios de la sucursal',
                    description: 'La informacion principal de la sucursal se actualizara en el sistema.',
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

            if (window.confirm('Seguro que deseas guardar los cambios de esta sucursal?')) {
                enviarFormulario();
            }
        });

        $scheduleRows.each(function () {
            syncScheduleRowState($(this));
        });

        $scheduleRows.on('change', '[data-schedule-enabled]', function () {
            syncScheduleRowState($(this).closest('[data-schedule-day-row]'));
        });
    });
</script>
@endsection

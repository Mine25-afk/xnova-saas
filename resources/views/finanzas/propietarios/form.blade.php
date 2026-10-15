@extends('layouts.main')

@section('title', ($isEditing ? 'Editar Propietario' : 'Nuevo Propietario') . ' — xNova')
@section('page-title', $isEditing ? 'Finanzas / Propietarios / Editar' : 'Finanzas / Propietarios / Nuevo')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/crud.css') }}">
@endsection

@section('content')
<div class="form-page">
    <x-card class="form-card form-card--premium">
        <div class="form-header">
            <div>
                <div class="form-badge">Propietarios de cuenta</div>
                <h1 class="form-title">{{ $isEditing ? 'Editar propietario' : 'Nuevo propietario' }}</h1>
                <p class="form-subtitle">
                    {{ $isEditing
                        ? 'Actualiza el nombre del propietario de cuenta. Este nombre se usa para agrupar cuentas, ingresos, gastos y movimientos.'
                        : 'Registra el nombre de la persona que participara en las finanzas personales o en pareja.' }}
                </p>
            </div>

            <div class="form-toolbar">
                <a href="{{ route('finanzas.propietarios.index') }}" class="btn btn-secondary">
                    <svg viewBox="0 0 24 24"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20z"/></svg>
                    Volver
                </a>
            </div>
        </div>

        <div class="form-body">
            <section class="form-panel">
                <form id="propietarioForm" class="form-grid" novalidate>
                    <div class="form-section">
                        <div>
                            <div class="form-section-title">Datos principales</div>
                            <p class="form-section-copy">El nombre identifica a la persona en cuentas, ingresos, gastos y movimientos financieros.</p>
                        </div>

                        <div class="form-field">
                        <x-input
                            id="propietario-nombre"
                            name="nombre"
                            label="Nombre"
                            placeholder="Ejemplo: Andrea"
                            icon="user"
                            value="{{ $propietario->nombre }}"
                        />
                            <div class="form-error" data-error-for="nombre"></div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <a href="{{ route('finanzas.propietarios.index') }}" class="btn btn-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-primary" id="propietarioSaveButton">
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
        var isEditing = @json($isEditing);
        var submitUrl = @json($isEditing ? route('finanzas.propietarios.update', $propietario) : route('finanzas.propietarios.store'));
        var csrfToken = $('meta[name="csrf-token"]').attr('content');
        var $form = $('#propietarioForm');
        var $nameInput = $('#propietario-nombre');
        var $saveButton = $('#propietarioSaveButton');

        function submitForm() {
            window.formUtils.clearErrors($form);
            window.formUtils.setLoadingState(true, $saveButton);
            window.uiBlocker.show('Guardando…');

            $.ajax({
                url: submitUrl,
                method: 'POST',
                data: {
                    _token: csrfToken,
                    _method: isEditing ? 'PUT' : 'POST',
                    nombre: $.trim($nameInput.val()),
                },
            }).done(function (response) {
                window.notify('success', 'Propietarios', response.message || 'Operacion completada.');

                if (!isEditing) {
                    $form[0].reset();
                    $nameInput.val('').trigger('focus');
                }
            }).fail(function (xhr) {
                if (xhr.status === 422) {
                    window.formUtils.setErrors(xhr.responseJSON.errors || {}, $form);
                    window.notify('warning', 'Validacion', 'Corrige los campos marcados para continuar.');
                    return;
                }

                window.notify('error', 'Propietarios', 'No se pudo guardar el propietario en este momento.');
            }).always(function () {
                window.formUtils.setLoadingState(false, $saveButton);
                window.uiBlocker.hide();
            });
        }

        $form.on('submit', function (event) {
            event.preventDefault();

            if (!isEditing) {
                submitForm();
                return;
            }

            if (window.$wireui && typeof window.$wireui.confirmDialog === 'function') {
                window.$wireui.confirmDialog({
                    title: 'Guardar cambios del propietario',
                    description: 'El nombre del propietario se actualizara en finanzas.',
                    icon: 'warning',
                    accept: {
                        label: 'Si, guardar',
                        color: 'info',
                        execute: submitForm,
                    },
                    reject: {
                        label: 'Cancelar',
                    },
                });

                return;
            }

            if (window.confirm('Seguro que deseas guardar los cambios de este propietario?')) {
                submitForm();
            }
        });
    });
</script>
@endsection

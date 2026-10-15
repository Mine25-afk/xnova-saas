@extends('layouts.main')

@section('title', ($isEditing ? 'Editar Puesto' : 'Nuevo Puesto') . ' - xNova')
@section('page-title', $isEditing ? 'Configuracion / Puestos / Editar' : 'Configuracion / Puestos / Nuevo')

@section('styles')
<style>
    .puesto-form-page {
        display: grid;
        gap: 20px;
    }

    .puesto-form-card {
        border-radius: 22px;
        overflow: hidden;
    }

    .puesto-form-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
        padding: 28px 30px 0;
        flex-wrap: wrap;
    }

    .puesto-form-title {
        font-size: 2rem;
        font-weight: 700;
        color: var(--text-primary);
        line-height: 1.1;
    }

    .puesto-form-subtitle {
        margin-top: 8px;
        font-size: 0.95rem;
        color: var(--text-secondary);
        max-width: 620px;
        line-height: 1.65;
    }

    .puesto-form-toolbar {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
    }

    .puesto-form-body {
        display: grid;
        gap: 20px;
        padding: 24px 30px 30px;
    }

    .puesto-form-panel {
        background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
        border: 1px solid rgba(226, 232, 240, 0.95);
        border-radius: 20px;
        padding: 24px;
    }

    .puesto-form-grid {
        display: grid;
        gap: 18px;
    }

    .puesto-form-field {
        display: grid;
        gap: 8px;
    }

    .puesto-form-error {
        min-height: 20px;
        color: #dc2626;
        font-size: 0.84rem;
        font-weight: 500;
    }

    .puesto-form-actions {
        display: flex;
        justify-content: flex-end;
        gap: 12px;
        padding-top: 8px;
        flex-wrap: wrap;
    }

    .puesto-form-status {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 14px;
        border-radius: 999px;
        font-size: 0.82rem;
        font-weight: 700;
        width: fit-content;
    }

    .puesto-form-status::before {
        content: '';
        width: 10px;
        height: 10px;
        border-radius: 999px;
        background: currentColor;
    }

    .puesto-form-status.is-active {
        background: rgba(16, 185, 129, 0.14);
        color: #047857;
    }

    .puesto-form-status.is-inactive {
        background: rgba(239, 68, 68, 0.14);
        color: #b91c1c;
    }

    @media (max-width: 960px) {
        .puesto-form-body {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 768px) {
        .puesto-form-header,
        .puesto-form-body {
            padding-left: 20px;
            padding-right: 20px;
        }

        .puesto-form-title {
            font-size: 1.75rem;
        }

        .puesto-form-panel,
        .puesto-form-side {
            padding: 20px;
        }
    }
</style>
@endsection

@section('content')
<div class="puesto-form-page">
    <x-card class="puesto-form-card">
        <div class="puesto-form-header">
            <div>
                <h1 class="puesto-form-title">{{ $isEditing ? 'Editar puesto' : 'Nuevo puesto' }}</h1>
                <p class="puesto-form-subtitle">
                    {{ $isEditing
                        ? 'Actualiza la información del puesto. Antes de guardar se te pedirá confirmación para aplicar los cambios.'
                        : 'Registra un puesto nuevo y, al guardarlo, el formulario quedará abierto para que sigas agregando más puestos sin regresar al listado.' }}
                </p>
            </div>

            <div class="puesto-form-toolbar">
                <a href="{{ route('puestos.page') }}" class="btn btn-secondary">
                    <svg viewBox="0 0 24 24"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20z"/></svg>
                    Volver
                </a>
            </div>
        </div>

        <div class="puesto-form-body">
            <section class="puesto-form-panel">
                <form id="puestoForm" class="puesto-form-grid" novalidate>
                    <div class="puesto-form-field">
                        <x-input
                            id="puesto-nombre"
                            name="nombre"
                            label="Nombre del puesto"
                            placeholder="Ejemplo: Supervisor de Caja"
                            icon="briefcase"
                            value="{{ $puesto->nombre }}"
                        />
                        <div class="puesto-form-error" data-error-for="nombre"></div>
                    </div>

                    <div class="puesto-form-actions">
                        <a href="{{ route('puestos.page') }}" class="btn btn-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-primary" id="puestoSaveButton">
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
        var submitUrl = @json($isEditing ? route('puestos.update', $puesto) : route('puestos.store'));
        var csrfToken = $('meta[name="csrf-token"]').attr('content');
        var $form = $('#puestoForm');
        var $nameInput = $('#puesto-nombre');
        var $saveButton = $('#puestoSaveButton');

        function submitForm() {
            window.formUtils.clearErrors($form);
            window.formUtils.setLoadingState(true, $saveButton);

            $.ajax({
                url: submitUrl,
                method: 'POST',
                data: {
                    _token: csrfToken,
                    _method: isEditing ? 'PUT' : 'POST',
                    nombre: $.trim($nameInput.val()),
                },
            }).done(function (response) {
                window.notify('success', 'Puestos', response.message || 'Operación completada.');

                if (!isEditing) {
                    $form[0].reset();
                    $nameInput.val('').trigger('focus');
                }
            }).fail(function (xhr) {
                if (xhr.status === 422) {
                    window.formUtils.setErrors(xhr.responseJSON.errors || {}, $form);
                    window.notify('warning', 'Validación', 'Corrige los campos marcados para continuar.');
                    return;
                }

                window.notify('error', 'Error', 'No se pudo guardar el puesto en este momento.');
            }).always(function () {
                window.formUtils.setLoadingState(false, $saveButton);
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
                    title: '¿Guardar cambios del puesto?',
                    description: 'La información del puesto se actualizará en el sistema.',
                    icon: 'warning',
                    accept: {
                        label: 'Sí, guardar',
                        color: 'info',
                        execute: submitForm,
                    },
                    reject: {
                        color: 'warning',
                        label: 'Cancelar',
                    },
                });

                return;
            }

            if (window.confirm('¿Seguro que deseas guardar los cambios de este puesto?')) {
                submitForm();
            }
        });
    });
</script>
@endsection

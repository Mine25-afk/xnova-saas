@php
    $productoActivo = $producto->exists ? (bool) $producto->active : true;
@endphp

@extends('layouts.main')

@section('title', ($isEditing ? 'Editar Producto' : 'Nuevo Producto') . ' - xNova')
@section('page-title', $isEditing ? 'Inventario / Productos / Editar' : 'Inventario / Productos / Nuevo')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/crud.css') }}">
    <style>
        .form-field--full{grid-column:1/-1}
        .media-field{display:grid;gap:12px}
        .media-upload{display:grid;gap:10px;padding:18px;border:1px dashed rgba(148,163,184,.45);border-radius:18px;background:#f8fbff}
        .media-upload input[type="file"]{width:100%;padding:12px;border:1px solid rgba(191,219,254,.9);border-radius:14px;background:#fff;font:inherit}
        .media-hint{font-size:.82rem;color:#64748b;line-height:1.6}
        .media-preview{width:100%;max-width:280px;border-radius:18px;border:1px solid rgba(148,163,184,.18);overflow:hidden;background:#fff;box-shadow:0 14px 30px rgba(148,163,184,.12)}
        .media-preview img{display:block;width:100%;aspect-ratio:1/1;object-fit:cover}
        .media-preview__meta{padding:12px 14px;font-size:.8rem;color:#475569}
        .toggle-field{display:flex;align-items:flex-start;gap:12px;padding:16px 18px;border-radius:18px;background:#f8fbff;border:1px solid rgba(191,219,254,.85)}
        .toggle-field input{margin-top:3px;width:18px;height:18px}
        .toggle-field strong{display:block;font-size:.92rem;margin-bottom:4px;color:#0f172a}
        .toggle-field span{display:block;font-size:.82rem;color:#64748b;line-height:1.6}
        .remove-image{display:flex;align-items:center;gap:8px;font-size:.84rem;color:#475569}
    </style>
@endsection

@section('content')
<div class="form-page">
    <x-card class="form-card">
        <div class="form-header">
            <div>
                <h1 class="form-title">{{ $isEditing ? 'Editar producto' : 'Nuevo producto' }}</h1>
                <p class="form-subtitle">
                    {{ $isEditing
                        ? 'Actualiza nombre, precio, estado e imagen opcional del producto.'
                        : 'Crea un producto para usarlo en inventario y en la reservacion web.' }}
                </p>
            </div>

            <div class="form-toolbar">
                <a href="{{ route('inventario.productos.index') }}" class="btn btn-secondary">
                    <svg viewBox="0 0 24 24"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20z"/></svg>
                    Volver
                </a>
            </div>
        </div>

        <div class="form-body">
            <section class="form-panel">
                <form id="productoForm" class="form-grid" novalidate>
                    <div class="form-field">
                        <x-input
                            id="producto-nombre"
                            name="nombre"
                            label="Nombre"
                            placeholder="Ejemplo: Consulta inicial"
                            icon="cube"
                            value="{{ $producto->nombre }}"
                        />
                        <div class="form-error" data-error-for="nombre"></div>
                    </div>

                    <div class="form-field">
                        <label class="form-label" for="producto-descripcion">Descripcion</label>
                        <textarea id="producto-descripcion" name="descripcion" class="form-input" rows="4" placeholder="Descripcion opcional del producto">{{ $producto->descripcion }}</textarea>
                        <div class="form-error" data-error-for="descripcion"></div>
                    </div>

                    <div class="form-field">
                        <div class="toggle-field">
                            <input id="producto-active" name="active" type="checkbox" value="1" @checked($productoActivo)>
                            <label for="producto-active">
                                <strong>Producto activo</strong>
                                <span>Solo los productos activos apareceran en la reservacion web y en los listados operativos.</span>
                            </label>
                        </div>
                        <div class="form-error" data-error-for="active"></div>
                    </div>

                    <div class="form-field">
                        <x-input
                            id="producto-precio"
                            name="precio"
                            type="number"
                            step="0.01"
                            min="0"
                            label="Precio"
                            placeholder="0.00"
                            icon="currency-dollar"
                            value="{{ $producto->precio }}"
                        />
                        <div class="form-error" data-error-for="precio"></div>
                    </div>

                    <div class="form-field form-field--full media-field">
                        <label class="form-label" for="producto-imagen">Imagen opcional</label>
                        <div class="media-upload">
                            <input id="producto-imagen" name="imagen" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                            <div class="media-hint">
                                Acepta JPG, PNG o WebP. La imagen se convierte a WebP y, si supera 1000x1000, se reduce automaticamente para mantener buena calidad y poco peso.
                            </div>

                            @if ($producto->imagen_url)
                                <div class="media-preview" id="producto-imagen-preview">
                                    <img src="{{ $producto->imagen_url }}" alt="{{ $producto->nombre ?: 'Vista previa del producto' }}">
                                    <div class="media-preview__meta">Imagen actual optimizada.</div>
                                </div>
                                <label class="remove-image" for="producto-remove-image">
                                    <input id="producto-remove-image" name="remove_image" type="checkbox" value="1">
                                    Eliminar imagen actual
                                </label>
                            @else
                                <div class="media-preview" id="producto-imagen-preview" hidden>
                                    <img src="" alt="Vista previa del producto">
                                    <div class="media-preview__meta">Vista previa de la nueva imagen.</div>
                                </div>
                            @endif
                        </div>
                        <div class="form-error" data-error-for="imagen"></div>
                    </div>

                    <div class="form-actions">
                        <a href="{{ route('inventario.productos.index') }}" class="btn btn-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-primary" id="productoSaveButton">
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
        var submitUrl = @json($isEditing ? route('inventario.productos.update', $producto) : route('inventario.productos.store'));
        var listUrl = @json(route('inventario.productos.index'));
        var csrfToken = $('meta[name="csrf-token"]').attr('content');
        var $form = $('#productoForm');
        var $nameInput = $('#producto-nombre');
        var $descriptionInput = $('#producto-descripcion');
        var $priceInput = $('#producto-precio');
        var $activeInput = $('#producto-active');
        var $imageInput = $('#producto-imagen');
        var $removeImageInput = $('#producto-remove-image');
        var $imagePreview = $('#producto-imagen-preview');
        var $imagePreviewImg = $imagePreview.find('img');
        var $saveButton = $('#productoSaveButton');

        function submitForm() {
            window.utilidadesFormulario.limpiarErrores($form);
            window.utilidadesFormulario.cambiarEstadoCarga(true, $saveButton);

            var formData = new FormData();
            formData.append('_token', csrfToken);
            formData.append('_method', isEditing ? 'PUT' : 'POST');
            formData.append('nombre', $.trim($nameInput.val()));
            formData.append('descripcion', $.trim($descriptionInput.val()));
            formData.append('precio', $.trim($priceInput.val()));
            formData.append('active', $activeInput.is(':checked') ? '1' : '0');

            if ($removeImageInput.length && $removeImageInput.is(':checked')) {
                formData.append('remove_image', '1');
            }

            if ($imageInput[0] && $imageInput[0].files && $imageInput[0].files[0]) {
                formData.append('imagen', $imageInput[0].files[0]);
            }

            $.ajax({
                url: submitUrl,
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
            }).done(function (response) {
                window.notify('success', 'Productos', response.message || 'Operacion completada.');
                window.location.href = listUrl;
            }).fail(function (xhr) {
                if (xhr.status === 422) {
                    window.utilidadesFormulario.mostrarErrores(xhr.responseJSON.errors || {}, $form);
                    window.notify('warning', 'Validacion', xhr.responseJSON?.message || 'Corrige los campos marcados para continuar.');
                    return;
                }

                window.notify('error', 'Productos', 'No se pudo guardar el producto en este momento.');
            }).always(function () {
                window.utilidadesFormulario.cambiarEstadoCarga(false, $saveButton);
            });
        }

        $imageInput.on('change', function () {
            var file = this.files && this.files[0];

            if (!file) {
                return;
            }

            var reader = new FileReader();

            reader.onload = function (event) {
                $imagePreview.removeAttr('hidden');
                $imagePreviewImg.attr('src', event.target.result);
            };

            reader.readAsDataURL(file);

            if ($removeImageInput.length) {
                $removeImageInput.prop('checked', false);
            }
        });

        $form.on('submit', function (event) {
            event.preventDefault();

            if (!isEditing) {
                submitForm();
                return;
            }

            if (window.$wireui && typeof window.$wireui.confirmDialog === 'function') {
                window.$wireui.confirmDialog({
                    title: 'Guardar cambios del producto',
                    description: 'La informacion del producto se actualizara en el sistema.',
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

            if (window.confirm('Seguro que deseas guardar los cambios de este producto?')) {
                submitForm();
            }
        });
    });
</script>
@endsection

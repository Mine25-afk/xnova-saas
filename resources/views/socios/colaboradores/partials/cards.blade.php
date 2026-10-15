@php
    $permissionCodes = $currentUserPermissionCodes ?? [];
    $canShowPermission = in_array('socios.colaboradores.detalles', $permissionCodes, true);
    $canEditPermission = in_array('socios.colaboradores.editar', $permissionCodes, true);
@endphp

@forelse ($data as $item)
    @php
        $canManageDefault = ! $item->es_por_defecto || ($canManageDefaultRecords ?? false);
        $canEdit = $canEditPermission && $canManageDefault;
        $canToggle = $canEdit;
    @endphp
    <article
        class="list-card-item {{ ($canShowPermission || $canEdit || $canToggle) ? 'js-empleado-card' : '' }}"
        data-dni="{{ $item->dni }}"
        data-name="{{ trim($item->nombres . ' ' . $item->apellidos) }}"
        data-active="{{ $item->active ? 1 : 0 }}"
        data-url="{{ $item->active ? route('socios.deactivate', $item) : route('socios.activate', $item) }}"
        data-method="{{ $item->active ? 'DELETE' : 'PATCH' }}"
        data-show-url="{{ $canShowPermission ? route('socios.show', $item) : '' }}"
        data-edit-url="{{ $canEdit ? route('socios.edit', $item) : '' }}"
        data-can-show="{{ $canShowPermission ? 1 : 0 }}"
        data-can-edit="{{ $canEdit ? 1 : 0 }}"
        data-can-toggle="{{ $canToggle ? 1 : 0 }}"
    >
        <div>
            <div class="list-card-item__title">{{ trim($item->nombres . ' ' . $item->apellidos) }}</div>
            <div class="list-card-item__meta">{{ $item->dni }}</div>
        </div>

        <div class="list-card-item__copy">
            Correo: {{ $item->correo ?: 'Sin correo' }}<br>
            Puesto: {{ $item->colaborador?->puesto?->nombre ?? 'Sin puesto' }}<br>
            Sucursal: {{ $item->colaborador?->sucursal?->nombre ?? 'Sin sucursal' }}<br>
            {{ $item->es_por_defecto ? 'Registro por defecto' : 'Registro manual' }}
        </div>

        <div class="list-card-item__footer">
            <span class="list-status {{ $item->active ? 'is-active' : 'is-inactive' }}">
                {{ $item->active ? 'Activo' : 'Inactivo' }}
            </span>

            <span class="list-card-item__hint">Acciones</span>
        </div>
    </article>
@empty
    <div class="list-empty">No se encontraron empleados.</div>
@endforelse

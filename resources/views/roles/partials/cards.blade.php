@php
    $permissionCodes = $currentUserPermissionCodes ?? [];
    $canShow = in_array('seguridad.roles.detalles', $permissionCodes, true);
    $canEdit = in_array('seguridad.roles.editar', $permissionCodes, true);
@endphp

@forelse ($data as $item)
    <article
        class="list-card-item {{ ($canShow || $canEdit) ? 'js-rol-card' : '' }}"
        data-id="{{ $item->id }}"
        data-name="{{ $item->nombre }}"
        data-show-url="{{ route('roles.show', $item) }}"
        data-edit-url="{{ route('roles.edit', $item) }}"
        data-can-show="{{ $canShow ? 1 : 0 }}"
        data-can-edit="{{ $canEdit ? 1 : 0 }}"
    >
        <div class="list-card-item__title">{{ $item->nombre }}</div>
        <div class="list-card-item__meta">{{ $item->permisos_count }} permisos asignados</div>

        <div class="list-card-item__footer">
            <span class="list-card-item__hint">{{ ($canShow || $canEdit) ? 'Ver acciones' : 'Sin acciones disponibles' }}</span>
        </div>
    </article>
@empty
    <div class="list-empty">No se encontraron registros.</div>
@endforelse

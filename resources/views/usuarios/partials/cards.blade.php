@php
    $permissionCodes = $currentUserPermissionCodes ?? [];
@endphp

@forelse ($data as $item)
    @php
        $canShow = in_array('seguridad.usuarios.detalles', $permissionCodes, true);
        $canEdit = in_array('seguridad.usuarios.editar', $permissionCodes, true);
        $isCompanyCreator = (int) ($creatorUserId ?? 0) === (int) $item->id;
        $canToggle = $item->active
            ? in_array('seguridad.usuarios.desactivar', $permissionCodes, true) && ! $isCompanyCreator
            : in_array('seguridad.usuarios.activar', $permissionCodes, true);
    @endphp
    <article
        class="list-card-item js-usuario-card"
        data-id="{{ $item->id }}"
        data-name="{{ $item->name }}"
        data-active="{{ $item->active ? 1 : 0 }}"
        data-url="{{ $item->active ? route('usuarios.deactivate', $item) : route('usuarios.activate', $item) }}"
        data-method="{{ $item->active ? 'DELETE' : 'PATCH' }}"
        data-show-url="{{ $canShow ? route('usuarios.show', $item) : '' }}"
        data-edit-url="{{ $canEdit ? route('usuarios.edit', $item) : '' }}"
        data-can-show="{{ $canShow ? 1 : 0 }}"
        data-can-edit="{{ $canEdit ? 1 : 0 }}"
        data-can-toggle="{{ $canToggle ? 1 : 0 }}"
    >
        <div>
            <div class="list-card-item__title">{{ $item->name }}</div>
            <div class="list-card-item__meta">{{ $item->email }}</div>
        </div>

        <div class="list-card-item__copy">
            Rol: {{ $item->rol?->nombre ?? 'Sin rol' }}<br>
            Sucursal: {{ $item->sucursal?->nombre ?? 'Sin sucursal' }}
        </div>

        <div class="list-card-item__footer">
            <span class="list-status {{ $item->active ? 'is-active' : 'is-inactive' }}">
                {{ $item->active ? 'Activo' : 'Inactivo' }}
            </span>

            <span class="list-card-item__hint">Acciones</span>
        </div>
    </article>
@empty
    <div class="list-empty">No se encontraron usuarios.</div>
@endforelse

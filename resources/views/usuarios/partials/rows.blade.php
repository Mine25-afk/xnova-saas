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
    <tr>
        <td>
            <div class="list-table__name">{{ $item->name }}</div>
            <div class="list-table__meta">{{ $item->email }}</div>
        </td>
        <td>{{ $item->rol?->nombre ?? 'Sin rol' }}</td>
        <td>{{ $item->sucursal?->nombre ?? 'Sin sucursal' }}</td>
        <td>
            <span class="list-status {{ $item->active ? 'is-active' : 'is-inactive' }}">
                {{ $item->active ? 'Activo' : 'Inactivo' }}
            </span>
        </td>
        <td>
            <div class="list-actions">
                @if ($canShow)
                    <a href="{{ route('usuarios.show', $item) }}" class="list-action list-action--info">Detalles</a>
                @endif

                @if ($canEdit)
                    <a href="{{ route('usuarios.edit', $item) }}" class="list-action list-action--edit">Editar</a>
                @endif

                @if ($canToggle)
                    <button
                        type="button"
                        class="list-action {{ $item->active ? 'list-action--danger' : 'list-action--success' }} js-cambiar-estado-usuario"
                        data-id="{{ $item->id }}"
                        data-name="{{ $item->name }}"
                        data-active="{{ $item->active ? 1 : 0 }}"
                        data-url="{{ $item->active ? route('usuarios.deactivate', $item) : route('usuarios.activate', $item) }}"
                        data-method="{{ $item->active ? 'DELETE' : 'PATCH' }}"
                    >
                        {{ $item->active ? 'Desactivar' : 'Activar' }}
                    </button>
                @endif
            </div>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="5" class="list-empty-row">No se encontraron usuarios.</td>
    </tr>
@endforelse

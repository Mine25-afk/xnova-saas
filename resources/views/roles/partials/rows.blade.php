@php
    $permissionCodes = $currentUserPermissionCodes ?? [];
    $canShow = in_array('seguridad.roles.detalles', $permissionCodes, true);
    $canEdit = in_array('seguridad.roles.editar', $permissionCodes, true);
@endphp

@forelse ($data as $item)
    <tr>
        <td>
            <div class="list-table__name">{{ $item->nombre }}</div>
            <div class="list-table__meta">{{ $item->permisos_count }} permisos asignados</div>
        </td>
        <td>
            {{ $item->permisos_count }}
        </td>
        <td>
            @if ($canShow || $canEdit)
                <div class="list-actions">
                    @if ($canShow)
                        <a href="{{ route('roles.show', $item) }}" class="list-action list-action--info">Detalles</a>
                    @endif

                    @if ($canEdit)
                    <a href="{{ route('roles.edit', $item) }}" class="list-action list-action--edit">Editar</a>
                    @endif
                </div>
            @endif
        </td>
    </tr>
@empty
    <tr>
        <td colspan="3" class="list-empty-row">No se encontraron registros.</td>
    </tr>
@endforelse

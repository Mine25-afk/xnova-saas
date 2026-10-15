@php
    $permissionCodes = $currentUserPermissionCodes ?? [];
    $canShow = in_array('socios.clientes.detalles', $permissionCodes, true);
    $canEditPermission = in_array('socios.clientes.editar', $permissionCodes, true);
@endphp

@forelse ($data as $item)
    @php
        $canManageDefault = ! $item->es_por_defecto || ($canManageDefaultRecords ?? false);
        $canEdit = $canEditPermission && $canManageDefault;
        $canToggle = $canEdit;
    @endphp
    <tr>
        <td>
            <div class="list-table__name">{{ trim($item->nombres . ' ' . $item->apellidos) }}</div>
            <div class="list-table__meta">
                {{ $item->es_por_defecto ? 'Registro por defecto' : 'Registro manual' }}
            </div>
        </td>
        <td>{{ $item->dni }}</td>
        <td>{{ $item->telefono }}</td>
        <td>{{ $item->correo ?: 'Sin correo' }}</td>
        <td>{{ $item->sexo === 'F' ? 'Femenino' : 'Masculino' }}</td>
        <td>
            <span class="list-status {{ $item->active ? 'is-active' : 'is-inactive' }}">
                {{ $item->active ? 'Activo' : 'Inactivo' }}
            </span>
        </td>
        <td>
            <div class="list-actions">
                @if ($canShow)
                    <a href="{{ route('socios.show', $item) }}" class="list-action list-action--info">Detalles</a>
                @endif

                @if ($canEdit)
                    <a href="{{ route('socios.edit', $item) }}" class="list-action list-action--edit">Editar</a>
                @endif

                @if ($canToggle)
                    <button
                        type="button"
                        class="list-action {{ $item->active ? 'list-action--danger' : 'list-action--success' }} js-cambiar-estado-cliente"
                        data-dni="{{ $item->dni }}"
                        data-name="{{ trim($item->nombres . ' ' . $item->apellidos) }}"
                        data-active="{{ $item->active ? 1 : 0 }}"
                        data-url="{{ $item->active ? route('socios.deactivate', $item) : route('socios.activate', $item) }}"
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
        <td colspan="7" class="list-empty-row">No se encontraron clientes.</td>
    </tr>
@endforelse

@php
    $permissionCodes = $currentUserPermissionCodes ?? [];
    $canShow = in_array('configuracion.sucursales.detalles', $permissionCodes, true);
    $canEdit = in_array('configuracion.sucursales.editar', $permissionCodes, true);
@endphp

@forelse ($data as $item)
    <tr>
        <td>
            <div class="list-table__name">{{ $item->nombre }}</div>
            <div class="list-table__meta">Registrada {{ optional($item->created_at)->format('d/m/Y h:i A') ?? 'Sin fecha' }}</div>
        </td>
        <td>{{ $item->colaboradores_count }}</td>
        <td>{{ $item->users_count }}</td>
        <td>
            <div class="list-actions">
                @if ($canShow)
                    <a href="{{ route('sucursales.show', $item) }}" class="list-action list-action--info">Detalles</a>
                @endif

                @if ($canEdit)
                    <a href="{{ route('sucursales.edit', $item) }}" class="list-action list-action--edit">Editar</a>
                @endif
            </div>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="4" class="list-empty-row">No se encontraron sucursales.</td>
    </tr>
@endforelse

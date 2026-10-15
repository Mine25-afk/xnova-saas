@php
    $permissionCodes = $currentUserPermissionCodes ?? [];
@endphp

@forelse ($data as $item)
    @php
        $canShow = in_array('citas.detalles', $permissionCodes, true);
        $canEdit = in_array('citas.editar', $permissionCodes, true) && $item->estaEditable();
        $canFinish = in_array('citas.terminar', $permissionCodes, true) && $item->estaEditable();
        $canCancel = in_array('citas.cancelar', $permissionCodes, true) && $item->estaEditable();
        $estadoClase = $item->estaTerminada() ? 'is-finished' : ($item->estaCancelada() ? 'is-cancelled' : 'is-reserved');
        $cliente = $item->nombreClienteMostrado();
        $empleado = trim(($item->colaborador?->socio?->nombres ?? '') . ' ' . ($item->colaborador?->socio?->apellidos ?? '')) ?: 'Sin empleado';
        $producto = $item->producto?->nombre;
        $precioProducto = $item->producto ? number_format((float) $item->producto->precio, 2) : null;
    @endphp
    <tr>
        <td>
            <div class="list-table__name">{{ optional($item->fecha)->format('d/m/Y') ?? 'Sin fecha' }}</div>
            <div class="list-table__meta">
                {{ substr((string) $item->hora_inicio, 0, 5) }} - {{ substr((string) $item->hora_salida, 0, 5) }} | {{ $item->descripcion }}
                @if ($producto)
                    | {{ $producto }} @if ($precioProducto) ({{ $precioProducto }}) @endif
                @endif
            </div>
        </td>
        <td>{{ $cliente }}</td>
        <td>{{ $empleado }}</td>
        <td>{{ $item->sucursal?->nombre ?? 'Sin sucursal' }}</td>
        <td>
            <span class="list-status {{ $estadoClase }}">{{ $item->estado?->nombre ?? 'Sin estado' }}</span>
        </td>
        <td>
            <div class="list-actions">
                @if ($canShow)
                    <a href="{{ route('citas.show', $item) }}" class="list-action list-action--info">Detalles</a>
                @endif

                @if ($canEdit)
                    <a href="{{ route('citas.edit', $item) }}" class="list-action list-action--edit">Editar</a>
                @endif

                @if ($canFinish)
                    <button
                        type="button"
                        class="list-action list-action--success js-cambiar-estado-cita"
                        data-action="finish"
                        data-url="{{ route('citas.finish', $item) }}"
                    >
                        Terminar
                    </button>
                @endif

                @if ($canCancel)
                    <button
                        type="button"
                        class="list-action list-action--danger js-cambiar-estado-cita"
                        data-action="cancel"
                        data-url="{{ route('citas.cancel', $item) }}"
                    >
                        Cancelar
                    </button>
                @endif
            </div>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="6" class="list-empty-row">No se encontraron citas.</td>
    </tr>
@endforelse

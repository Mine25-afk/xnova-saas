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
    <article
        class="list-card-item {{ ($canShow || $canEdit || $canFinish || $canCancel) ? 'js-cita-card' : '' }}"
        data-cliente="{{ $cliente }}"
        data-estado="{{ $item->estado?->nombre ?? 'Sin estado' }}"
        data-show-url="{{ route('citas.show', $item) }}"
        data-edit-url="{{ route('citas.edit', $item) }}"
        data-finish-url="{{ route('citas.finish', $item) }}"
        data-cancel-url="{{ route('citas.cancel', $item) }}"
        data-can-show="{{ $canShow ? 1 : 0 }}"
        data-can-edit="{{ $canEdit ? 1 : 0 }}"
        data-can-finish="{{ $canFinish ? 1 : 0 }}"
        data-can-cancel="{{ $canCancel ? 1 : 0 }}"
    >
        <div>
            <div class="list-card-item__title">{{ $cliente }}</div>
            <div class="list-card-item__meta">{{ optional($item->fecha)->format('d/m/Y') ?? 'Sin fecha' }} | {{ substr((string) $item->hora_inicio, 0, 5) }} - {{ substr((string) $item->hora_salida, 0, 5) }}</div>
        </div>

        <div class="list-card-item__copy">
            {{ $empleado }}<br>
            {{ $item->sucursal?->nombre ?? 'Sin sucursal' }}<br>
            {{ $item->descripcion }}
            @if ($producto)
                <br>{{ $producto }} @if ($precioProducto) ({{ $precioProducto }}) @endif
            @endif
        </div>

        <div class="list-card-item__footer">
            <span class="list-status {{ $estadoClase }}">{{ $item->estado?->nombre ?? 'Sin estado' }}</span>
            <span class="list-card-item__hint">{{ ($canShow || $canEdit || $canFinish || $canCancel) ? 'Ver acciones' : 'Sin acciones disponibles' }}</span>
        </div>
    </article>
@empty
    <div class="list-empty">No se encontraron citas.</div>
@endforelse

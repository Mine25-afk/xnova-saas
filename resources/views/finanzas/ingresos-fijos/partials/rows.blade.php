@forelse ($ingresos as $ingreso)
    <tr>
        <td><div class="list-table__name">{{ $ingreso->nombre }}</div><div class="list-table__meta">{{ ucfirst($ingreso->frecuencia) }}{{ $ingreso->dia_mes ? ' · Dia '.$ingreso->dia_mes : '' }}</div></td>
        <td>{{ $ingreso->propietario?->nombre ?? 'Sin propietario' }}</td>
        <td>{{ $ingreso->cuenta?->nombre ?? 'Sin cuenta fija' }}</td>
        <td><strong>${{ number_format((float) $ingreso->monto, 2) }}</strong></td>
        <td><div class="list-actions"><a href="{{ route('finanzas.ingresos-fijos.show', $ingreso) }}" class="list-action list-action--info">Detalles</a><a href="{{ route('finanzas.ingresos-fijos.edit', $ingreso) }}" class="list-action list-action--edit">Editar</a></div></td>
    </tr>
@empty
    <tr><td colspan="5" class="list-empty-row">No se encontraron ingresos fijos.</td></tr>
@endforelse

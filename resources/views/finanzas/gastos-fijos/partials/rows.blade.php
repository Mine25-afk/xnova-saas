@forelse ($gastos as $gasto)
    <tr>
        <td><div class="list-table__name">{{ $gasto->nombre }}</div><div class="list-table__meta">{{ $gasto->active ? 'Activo' : 'Inactivo' }}</div></td>
        <td>{{ $gasto->propietario?->nombre ?? 'Sin propietario' }}</td>
        <td><strong>${{ number_format((float) $gasto->monto, 2) }}</strong></td>
        <td><div class="list-actions"><a href="{{ route('finanzas.gastos-fijos.show', $gasto) }}" class="list-action list-action--info">Detalles</a><a href="{{ route('finanzas.gastos-fijos.edit', $gasto) }}" class="list-action list-action--edit">Editar</a></div></td>
    </tr>
@empty
    <tr><td colspan="4" class="list-empty-row">No se encontraron gastos fijos.</td></tr>
@endforelse

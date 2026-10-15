@forelse ($movimientos as $movimiento)
    <tr>
        <td><div class="list-table__name">{{ $movimiento->descripcion }}</div><div class="list-table__meta">{{ optional($movimiento->fecha)->format('d/m/Y') }} · {{ ucfirst($movimiento->tipo) }}{{ $movimiento->categoria ? ' · '.$movimiento->categoria : '' }}</div></td>
        <td>{{ $movimiento->cuenta?->nombre }} / {{ $movimiento->subcuenta?->nombre ?? 'General' }}@if ($movimiento->cuentaDestino) -> {{ $movimiento->cuentaDestino?->nombre }} / {{ $movimiento->subcuentaDestino?->nombre ?? 'General' }}@endif</td>
        <td>{{ $movimiento->propietario?->nombre ?? 'Sin propietario' }}</td>
        <td><strong>{{ $movimiento->cuenta?->moneda ?? 'USD' }} {{ number_format((float) $movimiento->monto, 2) }}</strong></td>
        <td><div class="list-actions"><a href="{{ route('finanzas.movimientos.show', $movimiento) }}" class="list-action list-action--info">Detalles</a><a href="{{ route('finanzas.movimientos.edit', $movimiento) }}" class="list-action list-action--edit">Editar</a></div></td>
    </tr>
@empty
    <tr><td colspan="5" class="list-empty-row">No se encontraron movimientos.</td></tr>
@endforelse

@forelse ($cuentas as $cuenta)
    @php($saldo = (float) ($balances[$cuenta->id] ?? 0))
    <tr>
        <td>
            <div class="list-table__name">{{ $cuenta->nombre }}</div>
            <div class="list-table__meta">{{ $cuenta->active ? 'Activa' : 'Inactiva' }}</div>
        </td>
        <td>{{ $cuenta->propietario?->nombre ?? 'Sin propietario' }}</td>
        <td>{{ ucfirst($cuenta->tipo) }}</td>
        <td><strong>{{ $cuenta->moneda }} {{ number_format($saldo, 2) }}</strong></td>
        <td>
            <div class="list-actions">
                <a href="{{ route('finanzas.cuentas.show', $cuenta) }}" class="list-action list-action--info">Detalles</a>
                <a href="{{ route('finanzas.cuentas.edit', $cuenta) }}" class="list-action list-action--edit">Editar</a>
            </div>
        </td>
    </tr>
@empty
    <tr><td colspan="5" class="list-empty-row">No se encontraron cuentas.</td></tr>
@endforelse

@forelse ($movimientos as $movimiento)
    <article class="list-card-item">
        <div><div class="list-card-item__title">{{ $movimiento->descripcion }}</div><div class="list-card-item__meta">{{ optional($movimiento->fecha)->format('d/m/Y') }} · {{ ucfirst($movimiento->tipo) }}{{ $movimiento->categoria ? ' · '.$movimiento->categoria : '' }}</div></div>
        <div class="list-card-item__meta">{{ $movimiento->cuenta?->nombre }} / {{ $movimiento->subcuenta?->nombre ?? 'General' }}@if ($movimiento->cuentaDestino) -> {{ $movimiento->cuentaDestino?->nombre }} / {{ $movimiento->subcuentaDestino?->nombre ?? 'General' }}@endif</div>
        <div class="list-card-item__meta"><strong>{{ $movimiento->cuenta?->moneda ?? 'USD' }} {{ number_format((float) $movimiento->monto, 2) }}</strong> · {{ $movimiento->propietario?->nombre ?? 'Sin propietario' }}</div>
        <div class="list-card-item__footer"><a href="{{ route('finanzas.movimientos.show', $movimiento) }}" class="list-action list-action--info">Detalles</a><a href="{{ route('finanzas.movimientos.edit', $movimiento) }}" class="list-action list-action--edit">Editar</a></div>
    </article>
@empty
    <div class="list-empty">No se encontraron movimientos.</div>
@endforelse

@forelse ($propietarios as $propietario)
    <article class="list-card-item list-card-item--owner">
        <div>
            <div class="list-card-item__title">{{ $propietario->nombre }}</div>
            <div class="list-card-item__meta">Registrado {{ optional($propietario->created_at)->format('d/m/Y h:i A') ?? 'sin fecha' }}</div>
        </div>

        <div class="list-card-metrics">
            <div class="list-card-metric">
                <span>{{ $propietario->cuentas_count }}</span>
                <small>{{ $propietario->cuentas_count === 1 ? 'Cuenta' : 'Cuentas' }}</small>
            </div>
        </div>

        <div class="list-card-item__footer">
            <a href="{{ route('finanzas.propietarios.show', $propietario) }}" class="list-action list-action--info">Detalles</a>
            <a href="{{ route('finanzas.propietarios.edit', $propietario) }}" class="list-action list-action--edit">Editar</a>
        </div>
    </article>
@empty
    <div class="list-empty">No se encontraron propietarios.</div>
@endforelse

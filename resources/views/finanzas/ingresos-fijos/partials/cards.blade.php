@forelse ($ingresos as $ingreso)
    <article class="fin-card {{ $ingreso->active ? '' : 'fin-card--muted' }}">
        <a href="{{ route('finanzas.ingresos-fijos.show', $ingreso) }}" class="fin-card__link" aria-label="Ver detalles de {{ $ingreso->nombre }}">Detalles</a>

        <div class="fin-card__row">
            <x-entity-icon :icon="$ingreso->icono ?: 'banknotes'" :fallback="$ingreso->nombre" size="md" />
            <div class="fin-card__main">
                <div class="fin-card__title">{{ $ingreso->nombre }}</div>
                <div class="fin-card__meta">
                    <span>{{ $ingreso->propietario?->nombre ?? 'Sin propietario' }}</span>
                    @if ($ingreso->cuenta?->nombre)
                        <span class="fin-card__dot"></span>
                        <span>{{ $ingreso->cuenta->nombre }}</span>
                    @endif
                </div>
            </div>
            <div class="fin-card__amount">{{ $monedaDefault ?? 'LPS' }} {{ number_format((float) $ingreso->monto, 2) }}</div>
        </div>

        <div class="fin-card__footer">
            <span class="fin-card__status fin-card__status--{{ $ingreso->active ? 'on' : 'off' }}">
                {{ $ingreso->active ? 'Activo' : 'Inactivo' }}
            </span>
            <div class="fin-card__actions">
                <a href="{{ route('finanzas.ingresos-fijos.edit', $ingreso) }}">Editar</a>
            </div>
        </div>
    </article>
@empty
    <div class="list-empty">No se encontraron ingresos fijos.</div>
@endforelse

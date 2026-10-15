@forelse ($gastos as $gasto)
    <article class="fin-card {{ $gasto->active ? '' : 'fin-card--muted' }}">
        <a href="{{ route('finanzas.gastos-fijos.show', $gasto) }}" class="fin-card__link" aria-label="Ver detalles de {{ $gasto->nombre }}">Detalles</a>

        <div class="fin-card__row">
            <x-entity-icon :icon="$gasto->icono ?: 'banknotes'" :fallback="$gasto->nombre" size="md" />
            <div class="fin-card__main">
                <div class="fin-card__title">{{ $gasto->nombre }}</div>
                <div class="fin-card__meta">
                    <span>{{ $gasto->propietario?->nombre ?? 'Sin propietario' }}</span>
                    @if ($gasto->frecuencia)
                        <span class="fin-card__dot"></span>
                        <span>{{ ucfirst($gasto->frecuencia) }}</span>
                    @endif
                </div>
            </div>
            <div class="fin-card__amount">{{ $monedaDefault ?? 'LPS' }} {{ number_format((float) $gasto->monto, 2) }}</div>
        </div>

        <div class="fin-card__footer">
            <span class="fin-card__status fin-card__status--{{ $gasto->active ? 'on' : 'off' }}">
                {{ $gasto->active ? 'Activo' : 'Inactivo' }}
            </span>
            <div class="fin-card__actions">
                <a href="{{ route('finanzas.gastos-fijos.edit', $gasto) }}">Editar</a>
            </div>
        </div>
    </article>
@empty
    <div class="list-empty">No se encontraron gastos fijos.</div>
@endforelse

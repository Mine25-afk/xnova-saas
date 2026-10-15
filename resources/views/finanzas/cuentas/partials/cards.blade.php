@forelse ($cuentas as $cuenta)
    @php($saldo = (float) ($balances[$cuenta->id] ?? 0))
    <article class="fin-card">
        <a href="{{ route('finanzas.cuentas.show', $cuenta) }}" class="fin-card__link" aria-label="Abrir {{ $cuenta->nombre }}">Abrir</a>

        <div class="fin-card__row">
            <x-entity-icon :icon="$cuenta->icono ?: 'wallet'" :fallback="$cuenta->nombre" size="md" />
            <div class="fin-card__main">
                <div class="fin-card__title">{{ $cuenta->nombre }}</div>
                <div class="fin-card__meta">
                    <span>{{ $cuenta->propietario?->nombre ?? 'Sin propietario' }}</span>
                    @if ($cuenta->tipo)
                        <span class="fin-card__dot"></span>
                        <span>{{ ucfirst($cuenta->tipo) }}</span>
                    @endif
                </div>
            </div>
            <div class="fin-card__amount">{{ $cuenta->moneda }} {{ number_format($saldo, 2) }}</div>
        </div>

        <div class="fin-card__footer">
            <span class="fin-card__status fin-card__status--on">Saldo actual</span>
            <div class="fin-card__actions">
                <a href="{{ route('finanzas.cuentas.edit', $cuenta) }}">Editar</a>
            </div>
        </div>
    </article>
@empty
    <div class="list-empty">No se encontraron cuentas.</div>
@endforelse

@forelse ($items as $item)
    @php($cobrada = (float) $item->saldo_pendiente <= 0)
    <article class="fin-card {{ $item->active ? '' : 'fin-card--muted' }}">
        <a href="{{ route('finanzas.cuentas-por-cobrar.show', $item) }}" class="fin-card__link" aria-label="Ver detalles de {{ $item->nombre }}">Detalles</a>

        <div class="fin-card__row">
            <x-entity-icon :icon="$item->icono ?: 'banknotes'" :fallback="$item->nombre" size="md" />
            <div class="fin-card__main">
                <div class="fin-card__title">{{ $item->nombre }}</div>
                <div class="fin-card__meta">
                    <span>{{ $item->deudor ?: 'Sin deudor' }}</span>
                    @if ($item->fecha_vencimiento)
                        <span class="fin-card__dot"></span>
                        <span>Vence {{ $item->fecha_vencimiento->format('d/m/Y') }}</span>
                    @endif
                </div>
            </div>
            <div class="fin-card__amount">{{ ($moneda ?? 'LPS') }} {{ number_format((float) $item->saldo_pendiente, 2) }}</div>
        </div>

        <div class="fin-card__footer">
            <span class="fin-card__status fin-card__status--{{ $cobrada ? 'off' : 'on' }}">
                {{ $cobrada ? 'Cobrada' : 'Pendiente' }}
            </span>
            <div class="fin-card__actions">
                @if (($puedeCobrar ?? false) && ! $cobrada)
                    <button type="button"
                            class="deuda-pay-btn"
                            data-id="{{ $item->id }}"
                            data-nombre="{{ $item->nombre }}"
                            data-saldo="{{ (float) $item->saldo_pendiente }}"
                            data-vencimiento="{{ $item->fecha_vencimiento?->format('d/m/Y') }}"
                            data-vencida="{{ $item->fecha_vencimiento && $item->fecha_vencimiento->isPast() ? '1' : '0' }}">
                        Cobrar
                    </button>
                @endif
                <a href="{{ route('finanzas.cuentas-por-cobrar.edit', $item) }}">Editar</a>
            </div>
        </div>
    </article>
@empty
    <div class="list-empty">No se encontraron cuentas por cobrar.</div>
@endforelse

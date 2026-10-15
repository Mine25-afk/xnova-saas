@forelse ($deudas as $deuda)
    @php($pagada = (float) $deuda->saldo_pendiente <= 0)
    <article class="fin-card {{ $deuda->active ? '' : 'fin-card--muted' }}">
        <a href="{{ route('finanzas.deudas.show', $deuda) }}" class="fin-card__link" aria-label="Ver detalles de {{ $deuda->nombre }}">Detalles</a>

        <div class="fin-card__row">
            <x-entity-icon :icon="$deuda->icono ?: 'banknotes'" :fallback="$deuda->nombre" size="md" />
            <div class="fin-card__main">
                <div class="fin-card__title">{{ $deuda->nombre }}</div>
                <div class="fin-card__meta">
                    <span>{{ $deuda->acreedor ?: 'Sin acreedor' }}</span>
                    @if ($deuda->fecha_vencimiento)
                        <span class="fin-card__dot"></span>
                        <span>Vence {{ $deuda->fecha_vencimiento->format('d/m/Y') }}</span>
                    @endif
                </div>
            </div>
            <div class="fin-card__amount">{{ ($moneda ?? 'LPS') }} {{ number_format((float) $deuda->saldo_pendiente, 2) }}</div>
        </div>

        <div class="fin-card__footer">
            <span class="fin-card__status fin-card__status--{{ $pagada ? 'off' : 'on' }}">
                {{ $pagada ? 'Pagada' : 'Pendiente' }}
            </span>
            <div class="fin-card__actions">
                @if (($puedePagar ?? false) && ! $pagada)
                    <button type="button"
                            class="deuda-pay-btn"
                            data-id="{{ $deuda->id }}"
                            data-nombre="{{ $deuda->nombre }}"
                            data-saldo="{{ (float) $deuda->saldo_pendiente }}"
                            data-vencimiento="{{ $deuda->fecha_vencimiento?->format('d/m/Y') }}"
                            data-vencida="{{ $deuda->fecha_vencimiento && $deuda->fecha_vencimiento->isPast() ? '1' : '0' }}">
                        Pagar
                    </button>
                @endif
                <a href="{{ route('finanzas.deudas.edit', $deuda) }}">Editar</a>
            </div>
        </div>
    </article>
@empty
    <div class="list-empty">No se encontraron deudas.</div>
@endforelse

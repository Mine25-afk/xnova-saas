@extends('layouts.main')

@section('title', 'Detalles de Deuda — xNova')
@section('page-title', 'Finanzas / Deudas / Detalles')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/detalle.css') }}">
@endsection

@php
    $pagada = $deuda->estaPagada();
    $pagado = max(0, (float) $deuda->monto_original - (float) $deuda->saldo_pendiente);
    $porcentaje = (float) $deuda->monto_original > 0 ? min(100, round($pagado / (float) $deuda->monto_original * 100)) : 0;
@endphp

@section('content')
<div class="detail-page">
    <x-card class="detail-card">
        <div class="detail-header">
            <div>
                <h1 class="detail-title">{{ $deuda->nombre }}</h1>
                <p class="detail-subtitle">Consulta el saldo pendiente y el historial de pagos de esta deuda.</p>
            </div>

            <div class="detail-toolbar">
                <a href="{{ route('finanzas.deudas.edit', $deuda) }}" class="btn btn-primary">
                    <svg viewBox="0 0 24 24"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zm17.71-10.21a1.003 1.003 0 0 0 0-1.42l-2.34-2.34a1.003 1.003 0 0 0-1.42 0l-1.83 1.83 3.75 3.75 1.84-1.82z"/></svg>
                    Editar
                </a>
                <a href="{{ route('finanzas.deudas.index') }}" class="btn btn-secondary">
                    <svg viewBox="0 0 24 24"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20z"/></svg>
                    Volver
                </a>
            </div>
        </div>

        <div class="detail-body">
            <section class="deuda-hero">
                <x-entity-icon :icon="$deuda->icono ?: 'banknotes'" :fallback="$deuda->nombre" size="xl" />

                <div class="deuda-hero__info">
                    <div class="deuda-hero__name">{{ $deuda->nombre }}</div>
                    <div class="deuda-hero__owner">{{ $deuda->acreedor ?: 'Sin acreedor' }}</div>
                    <div class="deuda-hero__badges">
                        <span class="deuda-hero__badge deuda-hero__badge--{{ $pagada ? 'on' : 'off' }}">
                            {{ $pagada ? 'Pagada' : 'Pendiente' }}
                        </span>
                        @if ($deuda->fecha_vencimiento)
                            <span class="deuda-hero__badge deuda-hero__badge--soft">Vence {{ $deuda->fecha_vencimiento->format('d/m/Y') }}</span>
                        @endif
                    </div>
                </div>

                <div class="deuda-hero__amount">
                    <span class="deuda-hero__amount-label">Saldo pendiente</span>
                    <span class="deuda-hero__amount-value">{{ $moneda }} {{ number_format((float) $deuda->saldo_pendiente, 2) }}</span>
                    <span class="deuda-hero__amount-sub">de {{ $moneda }} {{ number_format((float) $deuda->monto_original, 2) }}</span>
                </div>
            </section>

            <div class="deuda-progress">
                <div class="deuda-progress__bar"><span style="width: {{ $porcentaje }}%"></span></div>
                <div class="deuda-progress__label">Pagado {{ $moneda }} {{ number_format($pagado, 2) }} ({{ $porcentaje }}%)</div>
            </div>

            <section class="detail-panel">
                <div class="detail-panel-header"><div class="detail-panel-title">Datos de la deuda</div></div>
                <div class="detail-grid">
                    <article class="detail-item">
                        <span class="detail-label">Monto total</span>
                        <span class="detail-value">{{ $moneda }} {{ number_format((float) $deuda->monto_original, 2) }}</span>
                    </article>
                    <article class="detail-item">
                        <span class="detail-label">Saldo pendiente</span>
                        <span class="detail-value">{{ $moneda }} {{ number_format((float) $deuda->saldo_pendiente, 2) }}</span>
                    </article>
                    <article class="detail-item">
                        <span class="detail-label">Acreedor</span>
                        <span class="detail-value">{{ $deuda->acreedor ?: 'Sin acreedor' }}</span>
                    </article>
                    @if ($deuda->categoria)
                        <article class="detail-item">
                            <span class="detail-label">Categoría</span>
                            <span class="detail-value">{{ $deuda->categoria }}</span>
                        </article>
                    @endif
                    <article class="detail-item">
                        <span class="detail-label">Fecha</span>
                        <span class="detail-value">{{ optional($deuda->fecha)->format('d/m/Y') ?? 'Sin fecha' }}</span>
                    </article>
                    <article class="detail-item">
                        <span class="detail-label">Vencimiento</span>
                        <span class="detail-value">{{ optional($deuda->fecha_vencimiento)->format('d/m/Y') ?? 'No definido' }}</span>
                    </article>
                    <article class="detail-item">
                        <span class="detail-label">Estado</span>
                        <span class="detail-value">{{ $pagada ? 'Pagada' : 'Pendiente' }}</span>
                    </article>
                    @if ($deuda->notas)
                        <article class="detail-item">
                            <span class="detail-label">Notas</span>
                            <span class="detail-value">{{ $deuda->notas }}</span>
                        </article>
                    @endif
                </div>
            </section>

            <section class="detail-panel">
                <div class="detail-panel-header"><div class="detail-panel-title">Historial de pagos</div></div>
                @forelse ($deuda->pagos as $pago)
                    <div class="deuda-pago">
                        <div class="deuda-pago__info">
                            <span class="deuda-pago__desc">{{ $pago->descripcion }}</span>
                            <span class="deuda-pago__meta">{{ optional($pago->fecha)->format('d/m/Y') }} · {{ $pago->cuenta?->nombre ?? 'Cuenta' }} / {{ $pago->subcuenta?->nombre ?? 'Bolsillo' }}</span>
                        </div>
                        <span class="deuda-pago__amount">- {{ $moneda }} {{ number_format((float) $pago->monto, 2) }}</span>
                    </div>
                @empty
                    <div class="list-empty">Todavía no hay pagos registrados para esta deuda.</div>
                @endforelse
            </section>
        </div>
    </x-card>
</div>

<style>
    .deuda-hero {
        display: grid;
        grid-template-columns: auto 1fr auto;
        align-items: center;
        gap: 20px;
        padding: 22px 24px;
        margin-bottom: 16px;
        background: linear-gradient(135deg, rgba(68, 97, 242, .08) 0%, rgba(68, 97, 242, .02) 100%);
        border: 1px solid rgba(68, 97, 242, .15);
        border-radius: 18px;
    }
    .deuda-hero__info { min-width: 0; }
    .deuda-hero__name { font-size: 1.3rem; font-weight: 700; color: var(--text-primary, #161922); letter-spacing: -.01em; }
    .deuda-hero__owner { font-size: .92rem; color: var(--text-secondary, #5b616e); margin-top: 2px; }
    .deuda-hero__badges { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 10px; }
    .deuda-hero__badge { display: inline-flex; align-items: center; padding: 4px 10px; border-radius: 999px; font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; }
    .deuda-hero__badge--on { background: var(--success-soft, #d1fae5); color: var(--success, #10b981); }
    .deuda-hero__badge--off { background: var(--danger-soft, #fee2e2); color: var(--danger, #ef4444); }
    .deuda-hero__badge--soft { background: rgba(68, 97, 242, .12); color: var(--primary, #4461f2); }
    .deuda-hero__amount { display: flex; flex-direction: column; align-items: flex-end; text-align: right; }
    .deuda-hero__amount-label { font-size: .72rem; font-weight: 600; color: var(--text-secondary, #5b616e); text-transform: uppercase; letter-spacing: .04em; }
    .deuda-hero__amount-value { font-size: 1.7rem; font-weight: 800; color: var(--primary, #4461f2); letter-spacing: -.02em; margin-top: 4px; font-variant-numeric: tabular-nums; }
    .deuda-hero__amount-sub { font-size: .8rem; color: var(--text-muted, #9499a5); margin-top: 2px; }
    .deuda-progress { margin-bottom: 22px; }
    .deuda-progress__bar { height: 8px; border-radius: 999px; background: #eceef4; overflow: hidden; }
    .deuda-progress__bar span { display: block; height: 100%; border-radius: 999px; background: var(--success, #10b981); transition: width .4s var(--ease-out, ease); }
    .deuda-progress__label { font-size: .78rem; color: var(--text-secondary, #5b616e); margin-top: 6px; }
    .deuda-pago { display: flex; align-items: center; justify-content: space-between; gap: 14px; padding: 12px 4px; border-bottom: 1px solid var(--border, #e6e8ef); }
    .deuda-pago:last-child { border-bottom: none; }
    .deuda-pago__info { display: grid; gap: 2px; min-width: 0; }
    .deuda-pago__desc { font-size: .92rem; font-weight: 600; color: var(--text-primary, #161922); }
    .deuda-pago__meta { font-size: .78rem; color: var(--text-secondary, #5b616e); }
    .deuda-pago__amount { font-weight: 700; color: var(--danger, #ef4444); font-variant-numeric: tabular-nums; white-space: nowrap; }

    @media (max-width: 720px) {
        .deuda-hero { grid-template-columns: auto 1fr; padding: 18px; }
        .deuda-hero__amount { grid-column: 1 / -1; align-items: flex-start; text-align: left; padding-top: 6px; border-top: 1px dashed rgba(68, 97, 242, .2); margin-top: 4px; }
        .deuda-hero__amount-value { font-size: 1.5rem; }
    }
</style>
@endsection

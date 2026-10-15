@extends('layouts.main')

@section('title', 'Detalles de Gasto Fijo — xNova')
@section('page-title', 'Finanzas / Gastos Fijos / Detalles')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/detalle.css') }}">
@endsection

@section('content')
<div class="detail-page">
    <x-card class="detail-card">
        <div class="detail-header">
            <div>
                <h1 class="detail-title">{{ $gasto->nombre }}</h1>
                <p class="detail-subtitle">Consulta la información principal del gasto fijo.</p>
            </div>

            <div class="detail-toolbar">
                <a href="{{ route('finanzas.gastos-fijos.edit', $gasto) }}" class="btn btn-primary">
                    <svg viewBox="0 0 24 24"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zm17.71-10.21a1.003 1.003 0 0 0 0-1.42l-2.34-2.34a1.003 1.003 0 0 0-1.42 0l-1.83 1.83 3.75 3.75 1.84-1.82z"/></svg>
                    Editar
                </a>
                <a href="{{ route('finanzas.gastos-fijos.index') }}" class="btn btn-secondary">
                    <svg viewBox="0 0 24 24"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20z"/></svg>
                    Volver
                </a>
            </div>
        </div>

        <div class="detail-body">
            <section class="gasto-hero">
                <x-entity-icon :icon="$gasto->icono ?: 'banknotes'" :fallback="$gasto->nombre" size="xl" />

                <div class="gasto-hero__info">
                    <div class="gasto-hero__name">{{ $gasto->nombre }}</div>
                    <div class="gasto-hero__owner">{{ $gasto->propietario?->nombre ?? 'Sin propietario' }}</div>
                    <div class="gasto-hero__badges">
                        <span class="gasto-hero__badge gasto-hero__badge--{{ $gasto->active ? 'on' : 'off' }}">
                            {{ $gasto->active ? 'Activo' : 'Inactivo' }}
                        </span>
                        @if ($gasto->frecuencia)
                            <span class="gasto-hero__badge gasto-hero__badge--soft">{{ ucfirst($gasto->frecuencia) }}</span>
                        @endif
                        @if ($gasto->dia_mes)
                            <span class="gasto-hero__badge gasto-hero__badge--soft">Día {{ $gasto->dia_mes }}</span>
                        @endif
                    </div>
                </div>

                <div class="gasto-hero__amount">
                    <span class="gasto-hero__amount-label">Monto del compromiso</span>
                    <span class="gasto-hero__amount-value">${{ number_format((float) $gasto->monto, 2) }}</span>
                </div>
            </section>

            <section class="detail-panel">
                <div class="detail-panel-header"><div class="detail-panel-title">Datos del gasto</div></div>
                <div class="detail-grid">
                    <article class="detail-item">
                        <span class="detail-label">Nombre</span>
                        <span class="detail-value">{{ $gasto->nombre }}</span>
                    </article>
                    <article class="detail-item">
                        <span class="detail-label">Propietario</span>
                        <span class="detail-value">{{ $gasto->propietario?->nombre ?? 'Sin propietario' }}</span>
                    </article>
                    <article class="detail-item">
                        <span class="detail-label">Monto</span>
                        <span class="detail-value">${{ number_format((float) $gasto->monto, 2) }}</span>
                    </article>
                    <article class="detail-item">
                        <span class="detail-label">Frecuencia</span>
                        <span class="detail-value">{{ $gasto->frecuencia ? ucfirst($gasto->frecuencia) : 'No definida' }}</span>
                    </article>
                    @if ($gasto->dia_mes)
                        <article class="detail-item">
                            <span class="detail-label">Día del mes</span>
                            <span class="detail-value">{{ $gasto->dia_mes }}</span>
                        </article>
                    @endif
                    @if ($gasto->categoria)
                        <article class="detail-item">
                            <span class="detail-label">Categoría</span>
                            <span class="detail-value">{{ $gasto->categoria }}</span>
                        </article>
                    @endif
                    <article class="detail-item">
                        <span class="detail-label">Estado</span>
                        <span class="detail-value">{{ $gasto->active ? 'Activo' : 'Inactivo' }}</span>
                    </article>
                    <article class="detail-item">
                        <span class="detail-label">Registrado</span>
                        <span class="detail-value">{{ optional($gasto->created_at)->format('d/m/Y h:i A') ?? 'Sin fecha' }}</span>
                    </article>
                    <article class="detail-item">
                        <span class="detail-label">Última actualización</span>
                        <span class="detail-value">{{ optional($gasto->updated_at)->format('d/m/Y h:i A') ?? 'Sin fecha' }}</span>
                    </article>
                </div>
            </section>
        </div>
    </x-card>
</div>

<style>
    .gasto-hero {
        display: grid;
        grid-template-columns: auto 1fr auto;
        align-items: center;
        gap: 20px;
        padding: 22px 24px;
        margin-bottom: 22px;
        background: linear-gradient(135deg, rgba(68, 97, 242, .08) 0%, rgba(68, 97, 242, .02) 100%);
        border: 1px solid rgba(68, 97, 242, .15);
        border-radius: 18px;
    }
    .gasto-hero__info { min-width: 0; }
    .gasto-hero__name {
        font-size: 1.3rem;
        font-weight: 700;
        color: var(--text-primary, #17181c);
        letter-spacing: -.01em;
    }
    .gasto-hero__owner {
        font-size: .92rem;
        color: var(--text-secondary, #666b75);
        margin-top: 2px;
    }
    .gasto-hero__badges {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-top: 10px;
    }
    .gasto-hero__badge {
        display: inline-flex;
        align-items: center;
        padding: 4px 10px;
        border-radius: 999px;
        font-size: .7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .04em;
    }
    .gasto-hero__badge--on {
        background: var(--success-soft, #d1fae5);
        color: var(--success, #10b981);
    }
    .gasto-hero__badge--off {
        background: #f1f3f7;
        color: var(--text-secondary, #666b75);
    }
    .gasto-hero__badge--soft {
        background: rgba(68, 97, 242, .12);
        color: var(--primary, #4461f2);
    }
    .gasto-hero__amount {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        text-align: right;
    }
    .gasto-hero__amount-label {
        font-size: .72rem;
        font-weight: 600;
        color: var(--text-secondary, #666b75);
        text-transform: uppercase;
        letter-spacing: .04em;
    }
    .gasto-hero__amount-value {
        font-size: 1.7rem;
        font-weight: 800;
        color: var(--primary, #4461f2);
        letter-spacing: -.02em;
        margin-top: 4px;
    }

    @media (max-width: 720px) {
        .gasto-hero {
            grid-template-columns: auto 1fr;
            padding: 18px;
        }
        .gasto-hero__amount {
            grid-column: 1 / -1;
            align-items: flex-start;
            text-align: left;
            padding-top: 6px;
            border-top: 1px dashed rgba(68, 97, 242, .2);
            margin-top: 4px;
        }
        .gasto-hero__amount-value { font-size: 1.5rem; }
    }
</style>
@endsection

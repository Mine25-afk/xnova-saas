@extends('layouts.main')

@section('title', 'Reservacion Web - xNova')
@section('page-title', 'Inventario / Reservacion')

@section('styles')
    <style>
        .inventory-preview{display:grid;gap:24px}
        .inventory-hero{display:grid;gap:14px;padding:28px;border-radius:26px;background:linear-gradient(135deg,#0f172a,#1d4ed8 62%,#60a5fa);color:#fff;box-shadow:0 20px 44px rgba(30,41,59,.2)}
        .inventory-hero__eyebrow{font-size:.78rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;opacity:.82}
        .inventory-hero__title{font-size:clamp(1.9rem,3vw,2.8rem);line-height:1.04;font-weight:800}
        .inventory-hero__copy{max-width:720px;line-height:1.7;opacity:.9}
        .inventory-actions{display:flex;gap:12px;flex-wrap:wrap}
        .inventory-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:18px}
        .inventory-check{background:#fff;border:1px solid #e2e8f0;border-radius:22px;padding:20px;box-shadow:0 12px 24px rgba(148,163,184,.08);display:grid;gap:10px}
        .inventory-check__status{display:inline-flex;align-items:center;width:fit-content;padding:7px 10px;border-radius:999px;font-size:.76rem;font-weight:700}
        .inventory-check__status.is-ready{background:rgba(16,185,129,.12);color:#047857}
        .inventory-check__status.is-missing{background:rgba(239,68,68,.1);color:#b91c1c}
        .inventory-check__count{font-size:2rem;line-height:1;font-weight:800;color:#0f172a}
        .inventory-check__title{font-weight:700;color:#0f172a}
        .inventory-check__hint{color:#64748b;font-size:.88rem;line-height:1.6}
        .inventory-frame{background:#fff;border:1px solid #e2e8f0;border-radius:26px;padding:16px;box-shadow:0 18px 34px rgba(148,163,184,.1);display:grid;gap:14px}
        .inventory-frame__head{display:flex;justify-content:space-between;gap:16px;align-items:flex-start;flex-wrap:wrap}
        .inventory-frame__head h2{font-size:1.2rem;line-height:1.1;margin-bottom:6px}
        .inventory-frame__head p{color:#64748b;line-height:1.6}
        .inventory-frame iframe{width:100%;min-height:780px;border:none;border-radius:20px;background:#f8fafc}
        .inventory-empty{padding:22px;border-radius:22px;background:#fff7ed;border:1px solid #fdba74;color:#9a3412;line-height:1.7}
        @media (max-width: 960px){.inventory-grid{grid-template-columns:1fr}}
    </style>
@endsection

@section('content')
<div class="inventory-preview">
    <section class="inventory-hero">
        <div class="inventory-hero__eyebrow">Inventario / Vista previa</div>
        <h1 class="inventory-hero__title">Asi se vera tu reservacion web</h1>
        <p class="inventory-hero__copy">
            Comparte este enlace para recibir reservaciones publicas y revisa aqui mismo si ya tienes lo necesario para publicarlo.
            @if ($empresa->tipo?->tipo)
                Tu rubro actual es <strong>{{ $empresa->tipo->tipo }}</strong>.
            @endif
        </p>

        <div class="inventory-actions">
            <a href="{{ $publicReservationUrl }}" target="_blank" rel="noopener noreferrer" class="btn btn-primary">Abrir reservacion</a>
            <a href="{{ route('inventario.productos.index') }}" class="btn btn-secondary">Ir a productos</a>
            <a href="{{ route('inventario.reportes') }}" class="btn btn-secondary">Ver reporte</a>
        </div>
    </section>

    <section class="inventory-grid">
        @foreach ($requirements as $requirement)
            <article class="inventory-check">
                <span class="inventory-check__status {{ $requirement['ready'] ? 'is-ready' : 'is-missing' }}">
                    {{ $requirement['ready'] ? 'Listo' : 'Pendiente' }}
                </span>
                <div class="inventory-check__count">{{ $requirement['count'] }}</div>
                <div class="inventory-check__title">{{ $requirement['label'] }}</div>
                <div class="inventory-check__hint">{{ $requirement['hint'] }}</div>
            </article>
        @endforeach
    </section>

    @if ($canPreview)
        <section class="inventory-frame">
            <div class="inventory-frame__head">
                <div>
                    <h2>Vista previa embebida</h2>
                    <p>Este preview usa tu enlace publico real, asi que cualquier cambio en productos o disponibilidad se reflejara aqui.</p>
                </div>

                <a href="{{ $publicReservationUrl }}" target="_blank" rel="noopener noreferrer" class="btn btn-secondary">Abrir en pantalla completa</a>
            </div>

            <iframe src="{{ $publicReservationUrl }}" title="Vista previa de reservacion web"></iframe>
        </section>
    @else
        <section class="inventory-empty">
            Aun no podemos mostrar la vista completa porque faltan requisitos basicos. Completa los elementos marcados arriba y vuelve a esta pantalla para revisar el resultado final.
        </section>
    @endif
</div>
@endsection

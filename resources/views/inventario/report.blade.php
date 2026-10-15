@extends('layouts.main')

@section('title', 'Reporte de Catalogo - xNova')
@section('page-title', 'Inventario / Reporte')

@section('styles')
    <style>
        .inventory-report{display:grid;gap:22px}
        .report-stats{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:18px}
        .report-stat{padding:22px;border-radius:22px;border:1px solid #e2e8f0;background:#fff;box-shadow:0 14px 28px rgba(148,163,184,.08);display:grid;gap:8px}
        .report-stat__label{color:#64748b;font-size:.82rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em}
        .report-stat__value{font-size:2rem;line-height:1;font-weight:800;color:#0f172a}
        .report-card{background:#fff;border:1px solid #e2e8f0;border-radius:24px;box-shadow:0 16px 34px rgba(148,163,184,.08);overflow:hidden}
        .report-card__head{padding:22px 24px;border-bottom:1px solid #e2e8f0;display:flex;justify-content:space-between;gap:16px;align-items:center;flex-wrap:wrap}
        .report-card__head p{color:#64748b;line-height:1.6;margin-top:6px}
        .report-table{width:100%;border-collapse:collapse}
        .report-table th,.report-table td{padding:16px 24px;text-align:left;border-bottom:1px solid #e2e8f0;vertical-align:middle}
        .report-table th{font-size:.82rem;text-transform:uppercase;letter-spacing:.05em;color:#64748b}
        .report-product{display:flex;align-items:center;gap:14px}
        .report-thumb{width:52px;height:52px;border-radius:16px;object-fit:cover;border:1px solid rgba(148,163,184,.18);background:linear-gradient(135deg,#eff6ff,#dbeafe);flex-shrink:0;display:grid;place-items:center;color:#1d4ed8;font-weight:800}
        .report-status{display:inline-flex;align-items:center;padding:6px 10px;border-radius:999px;font-size:.75rem;font-weight:700}
        .report-status.is-active{background:rgba(16,185,129,.12);color:#047857}
        .report-status.is-inactive{background:rgba(148,163,184,.16);color:#475569}
        .report-empty{padding:28px 24px;color:#64748b}
        @media (max-width: 980px){.report-stats{grid-template-columns:1fr}.report-card{overflow:auto}.report-table{min-width:760px}}
    </style>
@endsection

@section('content')
<div class="inventory-report">
    <section class="report-stats">
        <article class="report-stat">
            <span class="report-stat__label">Productos totales</span>
            <div class="report-stat__value">{{ $stats['total'] }}</div>
        </article>
        <article class="report-stat">
            <span class="report-stat__label">Activos / Inactivos</span>
            <div class="report-stat__value">{{ $stats['active'] }} / {{ $stats['inactive'] }}</div>
        </article>
        <article class="report-stat">
            <span class="report-stat__label">Con imagen</span>
            <div class="report-stat__value">{{ $stats['with_image'] }}</div>
        </article>
        <article class="report-stat">
            <span class="report-stat__label">Precio promedio</span>
            <div class="report-stat__value">${{ number_format((float) $stats['average_price'], 2) }}</div>
        </article>
        <article class="report-stat">
            <span class="report-stat__label">Precio mas alto</span>
            <div class="report-stat__value">${{ number_format((float) $stats['highest_price'], 2) }}</div>
        </article>
        <article class="report-stat">
            <span class="report-stat__label">Precio mas bajo</span>
            <div class="report-stat__value">${{ number_format((float) $stats['lowest_price'], 2) }}</div>
        </article>
    </section>

    <section class="report-card">
        <div class="report-card__head">
            <div>
                <h2>Reporte de catalogo</h2>
                <p>Este resumen te permite revisar rapidamente que productos estan visibles, cuales tienen imagen y como se distribuyen sus precios.</p>
            </div>

            <div class="form-toolbar">
                <a href="{{ route('inventario.productos.index') }}" class="btn btn-secondary">Productos</a>
                <a href="{{ route('inventario.reservacion') }}" class="btn btn-primary">Reservacion</a>
            </div>
        </div>

        @if ($productos->isEmpty())
            <div class="report-empty">Todavia no has creado productos para el catalogo.</div>
        @else
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Estado</th>
                        <th>Precio</th>
                        <th>Imagen</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($productos as $producto)
                        <tr>
                            <td>
                                <div class="report-product">
                                    @if ($producto->imagen_url)
                                        <img src="{{ $producto->imagen_url }}" alt="{{ $producto->nombre }}" class="report-thumb">
                                    @else
                                        <div class="report-thumb">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($producto->nombre, 0, 1)) }}</div>
                                    @endif

                                    <div>
                                        <strong>{{ $producto->nombre }}</strong>
                                        <div>{{ $producto->descripcion ?: 'Sin descripcion' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="report-status {{ $producto->active ? 'is-active' : 'is-inactive' }}">
                                    {{ $producto->active ? 'Activo' : 'Inactivo' }}
                                </span>
                            </td>
                            <td>${{ number_format((float) $producto->precio, 2) }}</td>
                            <td>{{ $producto->imagen_url ? 'Si' : 'No' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </section>
</div>
@endsection

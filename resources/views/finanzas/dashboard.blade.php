@extends('layouts.main')

@section('title', 'Dashboard Financiero — xNova')
@section('page-title', 'Finanzas / Dashboard')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/listado.css') }}">
    <style>
        .fin-dash { display:grid; gap:18px; }

        .fin-quick { display:flex; flex-wrap:wrap; gap:10px; }

        /* KPIs */
        .kpi-grid {
            display:grid;
            grid-template-columns:repeat(auto-fit, minmax(210px, 1fr));
            gap:14px;
        }
        .kpi {
            position:relative;
            padding:18px 20px;
            background:var(--surface, #fff);
            border:1px solid var(--border, #e7e9ef);
            border-radius:16px;
            box-shadow:0 6px 18px rgba(15,23,42,.04);
            overflow:hidden;
        }
        .kpi::before {
            content:""; position:absolute; left:0; top:0; bottom:0; width:4px;
            background:var(--primary, #4461f2);
        }
        .kpi--good::before { background:var(--success, #10b981); }
        .kpi--risk::before { background:var(--danger, #ef4444); }
        .kpi--info::before { background:#f59e0b; }
        .kpi__label {
            font-size:.74rem; font-weight:700; color:var(--text-secondary, #666b75);
            text-transform:uppercase; letter-spacing:.05em;
        }
        .kpi__value { margin-top:8px; font-size:1.6rem; font-weight:800; color:var(--text-primary, #17181c); letter-spacing:-.02em; }
        .kpi__value.is-negative { color:var(--danger, #ef4444); }
        .kpi__value.is-positive { color:var(--success, #10b981); }
        .kpi__hint { margin-top:4px; font-size:.78rem; color:var(--text-secondary, #666b75); }

        /* Charts */
        .chart-grid { display:grid; grid-template-columns:minmax(0, 1.6fr) minmax(0, 1fr); gap:16px; }
        @media (max-width: 980px) { .chart-grid { grid-template-columns:1fr; } }

        .chart-card {
            background:var(--surface, #fff);
            border:1px solid var(--border, #e7e9ef);
            border-radius:16px;
            box-shadow:0 6px 18px rgba(15,23,42,.04);
            padding:20px 22px;
            display:flex; flex-direction:column;
        }
        .chart-card__head { display:flex; align-items:flex-start; justify-content:space-between; gap:12px; margin-bottom:14px; }
        .chart-card__title { font-size:1rem; font-weight:700; color:var(--text-primary, #17181c); }
        .chart-card__copy { font-size:.8rem; color:var(--text-secondary, #666b75); margin-top:2px; }
        .chart-card__canvas-wrap { position:relative; flex:1; min-height:240px; }
        .chart-empty { display:grid; place-items:center; min-height:240px; color:var(--text-secondary, #666b75); font-size:.88rem; text-align:center; }

        /* Listas inferiores */
        .fin-bottom { display:grid; grid-template-columns:minmax(0,1fr) minmax(320px,.85fr); gap:16px; }
        @media (max-width: 980px) { .fin-bottom { grid-template-columns:1fr; } }
        .fin-list { display:grid; gap:8px; }
        .fin-row {
            display:flex; align-items:center; justify-content:space-between; gap:12px;
            padding:12px 14px; border:1px solid var(--border, #e7e9ef); border-radius:12px; background:#fff;
        }
        .fin-row strong { font-size:.92rem; color:var(--text-primary, #17181c); }
        .fin-row small { display:block; color:var(--text-secondary, #666b75); margin-top:2px; font-size:.78rem; }
        .fin-amount { font-weight:700; white-space:nowrap; }
        .fin-amount.is-negative { color:var(--danger, #ef4444); }
        .fin-amount.is-positive { color:var(--success, #10b981); }
    </style>
@endsection

@section('content')
@php
    $moneda = $cuentas->first()->moneda ?? 'LPS';
@endphp
<div class="fin-dash">
    @if (! empty($pendientesCount))
        <a href="{{ route('finanzas.pendientes.index') }}" class="pend-banner">
            <span class="pend-banner__icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
            </span>
            <span class="pend-banner__text">
                Tienes <strong>{{ $pendientesCount }}</strong> {{ $pendientesCount === 1 ? 'operación pendiente' : 'operaciones pendientes' }} de confirmar
            </span>
            <span class="pend-banner__cta">Revisar
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
            </span>
        </a>
        <style>
            .pend-banner { display:flex; align-items:center; gap:12px; padding:13px 16px; border-radius:14px;
                background:#fef3c7; border:1px solid #fde68a; color:#92400e; text-decoration:none; font-weight:600; transition:filter .2s ease; }
            .pend-banner:hover { filter:brightness(.98); }
            .pend-banner__icon { width:34px; height:34px; border-radius:10px; background:#fde68a; display:grid; place-items:center; flex-shrink:0; }
            .pend-banner__icon svg { width:19px; height:19px; }
            .pend-banner__text { flex:1; font-size:.92rem; }
            .pend-banner__cta { display:inline-flex; align-items:center; gap:5px; font-weight:700; font-size:.88rem; white-space:nowrap; }
            .pend-banner__cta svg { width:16px; height:16px; }
        </style>
    @endif

    <div class="fin-quick">
        <a href="{{ route('finanzas.cuentas.index') }}" class="btn btn-primary">Administrar cuentas</a>
        <a href="{{ route('finanzas.reportes') }}" class="btn btn-secondary">Ver reportes</a>
        @if (! empty($pendientesCount))
            <a href="{{ route('finanzas.pendientes.index') }}" class="btn btn-secondary">Pendientes ({{ $pendientesCount }})</a>
        @endif
    </div>

    {{-- KPIs --}}
    <div class="kpi-grid">
        <div class="kpi {{ $saldoTotal >= 0 ? '' : 'kpi--risk' }}">
            <div class="kpi__label">Saldo total</div>
            <div class="kpi__value {{ $saldoTotal < 0 ? 'is-negative' : '' }}">{{ $moneda }} {{ number_format((float) $saldoTotal, 2) }}</div>
            <div class="kpi__hint">Suma de todas las cuentas</div>
        </div>
        <div class="kpi kpi--good">
            <div class="kpi__label">Ingresos del mes</div>
            <div class="kpi__value is-positive">{{ $moneda }} {{ number_format((float) $ingresosMes, 2) }}</div>
            <div class="kpi__hint">{{ now()->locale('es')->isoFormat('MMMM YYYY') }}</div>
        </div>
        <div class="kpi kpi--risk">
            <div class="kpi__label">Gastos del mes</div>
            <div class="kpi__value is-negative">{{ $moneda }} {{ number_format((float) $gastosMes, 2) }}</div>
            <div class="kpi__hint">{{ now()->locale('es')->isoFormat('MMMM YYYY') }}</div>
        </div>
        <div class="kpi {{ $flujoNeto >= 0 ? 'kpi--good' : 'kpi--risk' }}">
            <div class="kpi__label">Flujo neto del mes</div>
            <div class="kpi__value {{ $flujoNeto < 0 ? 'is-negative' : 'is-positive' }}">{{ $moneda }} {{ number_format((float) $flujoNeto, 2) }}</div>
            <div class="kpi__hint">Margen {{ number_format((float) $margenNeto, 1) }}%</div>
        </div>
        <div class="kpi kpi--info">
            <div class="kpi__label">Proyección fija</div>
            <div class="kpi__value">{{ $moneda }} {{ number_format((float) $ingresosFijos - (float) $gastosFijos, 2) }}</div>
            <div class="kpi__hint">Fijos: +{{ number_format((float) $ingresosFijos, 0) }} / −{{ number_format((float) $gastosFijos, 0) }}</div>
        </div>
    </div>

    {{-- GRÁFICAS --}}
    <div class="chart-grid">
        <div class="chart-card">
            <div class="chart-card__head">
                <div>
                    <div class="chart-card__title">Ingresos vs Gastos</div>
                    <div class="chart-card__copy">Tendencia de los últimos 6 meses</div>
                </div>
            </div>
            <div class="chart-card__canvas-wrap">
                <canvas id="chartTendencia"></canvas>
            </div>
        </div>

        <div class="chart-card">
            <div class="chart-card__head">
                <div>
                    <div class="chart-card__title">Gastos por categoría</div>
                    <div class="chart-card__copy">Mes actual</div>
                </div>
            </div>
            @if ($gastosCategoria->isEmpty())
                <div class="chart-empty">No hay gastos registrados este mes.</div>
            @else
                <div class="chart-card__canvas-wrap">
                    <canvas id="chartCategorias"></canvas>
                </div>
            @endif
        </div>
    </div>

    {{-- SALDO POR CUENTA --}}
    <div class="chart-card">
        <div class="chart-card__head">
            <div>
                <div class="chart-card__title">Saldo por cuenta</div>
                <div class="chart-card__copy">Estado actual calculado desde los movimientos</div>
            </div>
        </div>
        @if (empty($saldoCuentas))
            <div class="chart-empty">Crea tu primera cuenta para ver el saldo.</div>
        @else
            <div class="chart-card__canvas-wrap" style="min-height:220px">
                <canvas id="chartCuentas"></canvas>
            </div>
        @endif
    </div>

    {{-- LISTAS --}}
    <div class="fin-bottom">
        <x-card class="list-card">
            <x-slot name="header">
                <div class="list-card-header"><div><div class="list-card-title">Cuentas</div><div class="list-card-subtitle">Saldo calculado desde movimientos.</div></div></div>
            </x-slot>
            <div class="fin-list">
                @forelse ($cuentas as $cuenta)
                    @php($saldo = (float) ($balances[$cuenta->id] ?? 0))
                    <a href="{{ route('finanzas.cuentas.show', $cuenta) }}" class="fin-row" style="text-decoration:none">
                        <div><strong>{{ $cuenta->nombre }}</strong><small>{{ $cuenta->propietario?->nombre ?? 'Sin propietario' }} · {{ ucfirst($cuenta->tipo) }}</small></div>
                        <div class="fin-amount {{ $saldo < 0 ? 'is-negative' : 'is-positive' }}">{{ $cuenta->moneda }} {{ number_format($saldo, 2) }}</div>
                    </a>
                @empty
                    <div class="list-empty">Crea tu primera cuenta para empezar.</div>
                @endforelse
            </div>
        </x-card>

        <x-card class="list-card">
            <x-slot name="header">
                <div class="list-card-header"><div><div class="list-card-title">Movimientos recientes</div><div class="list-card-subtitle">Últimas entradas, gastos y transferencias.</div></div></div>
            </x-slot>
            <div class="fin-list">
                @forelse ($movimientosRecientes as $movimiento)
                    <div class="fin-row">
                        <div><strong>{{ $movimiento->descripcion }}</strong><small>{{ $movimiento->fecha?->format('d/m/Y') }} · {{ $movimiento->cuenta?->nombre }}{{ $movimiento->cuentaDestino ? ' → '.$movimiento->cuentaDestino->nombre : '' }}</small></div>
                        <div class="fin-amount {{ $movimiento->tipo === 'gasto' ? 'is-negative' : 'is-positive' }}">{{ $movimiento->tipo === 'gasto' ? '−' : '+' }}{{ number_format((float) $movimiento->monto, 2) }}</div>
                    </div>
                @empty
                    <div class="list-empty">Todavía no hay movimientos.</div>
                @endforelse
            </div>
        </x-card>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.6/dist/chart.umd.min.js"></script>
<script>
    (function () {
        if (typeof Chart === 'undefined') { return; }

        Chart.defaults.font.family = 'Poppins, sans-serif';
        Chart.defaults.color = '#666b75';

        var moneda = @json($moneda);
        var fmt = function (n) {
            return moneda + ' ' + Number(n || 0).toLocaleString('es-HN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        };

        // ---- Tendencia: Ingresos vs Gastos ----
        var tendencia = @json($tendencia);
        var ctxT = document.getElementById('chartTendencia');
        if (ctxT) {
            new Chart(ctxT, {
                type: 'bar',
                data: {
                    labels: tendencia.map(function (t) { return t.mes; }),
                    datasets: [
                        {
                            label: 'Ingresos',
                            data: tendencia.map(function (t) { return t.ingresos; }),
                            backgroundColor: 'rgba(16,185,129,.85)',
                            borderRadius: 6,
                            maxBarThickness: 26,
                        },
                        {
                            label: 'Gastos',
                            data: tendencia.map(function (t) { return t.gastos; }),
                            backgroundColor: 'rgba(239,68,68,.85)',
                            borderRadius: 6,
                            maxBarThickness: 26,
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom', labels: { usePointStyle: true, padding: 16 } },
                        tooltip: { callbacks: { label: function (c) { return c.dataset.label + ': ' + fmt(c.parsed.y); } } }
                    },
                    scales: {
                        x: { grid: { display: false } },
                        y: { beginAtZero: true, ticks: { callback: function (v) { return Number(v).toLocaleString('es-HN'); } }, grid: { color: '#eef0f6' } }
                    }
                }
            });
        }

        // ---- Gastos por categoría (dona) ----
        var categorias = @json($gastosCategoria);
        var catLabels = Object.keys(categorias);
        var catValues = catLabels.map(function (k) { return categorias[k]; });
        var ctxC = document.getElementById('chartCategorias');
        if (ctxC && catLabels.length) {
            var palette = ['#4461f2', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4', '#ec4899', '#64748b'];
            new Chart(ctxC, {
                type: 'doughnut',
                data: {
                    labels: catLabels,
                    datasets: [{ data: catValues, backgroundColor: palette, borderWidth: 2, borderColor: '#fff' }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '62%',
                    plugins: {
                        legend: { position: 'bottom', labels: { usePointStyle: true, padding: 12, boxWidth: 8 } },
                        tooltip: { callbacks: { label: function (c) { return c.label + ': ' + fmt(c.parsed); } } }
                    }
                }
            });
        }

        // ---- Saldo por cuenta (barras horizontales) ----
        var saldoCuentas = @json($saldoCuentas);
        var ctxA = document.getElementById('chartCuentas');
        if (ctxA && saldoCuentas.length) {
            new Chart(ctxA, {
                type: 'bar',
                data: {
                    labels: saldoCuentas.map(function (c) { return c.nombre; }),
                    datasets: [{
                        label: 'Saldo',
                        data: saldoCuentas.map(function (c) { return c.saldo; }),
                        backgroundColor: saldoCuentas.map(function (c) { return c.saldo < 0 ? 'rgba(239,68,68,.85)' : 'rgba(68,97,242,.85)'; }),
                        borderRadius: 6,
                        maxBarThickness: 30,
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: { callbacks: { label: function (c) { return fmt(c.parsed.x); } } }
                    },
                    scales: {
                        x: { beginAtZero: true, ticks: { callback: function (v) { return Number(v).toLocaleString('es-HN'); } }, grid: { color: '#eef0f6' } },
                        y: { grid: { display: false } }
                    }
                }
            });
        }
    })();
</script>
@endsection

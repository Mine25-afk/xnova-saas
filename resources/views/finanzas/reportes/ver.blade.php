@extends('layouts.main')

@section('title', $reporte['titulo'] . ' — xNova')
@section('page-title', 'Finanzas / Reportes / ' . $reporte['titulo'])

@section('styles')
    <style>
        .rv-page { display:grid; gap:18px; }
        .rv-toolbar { display:flex; flex-wrap:wrap; gap:10px; }
        .rv-card { background:#fff; border:1px solid var(--border,#e7e9ef); border-radius:16px; box-shadow:0 6px 18px rgba(15,23,42,.04); overflow:hidden; }
        .rv-head { padding:22px 24px; border-bottom:1px solid var(--border,#e7e9ef); }
        .rv-title { font-size:1.3rem; font-weight:800; color:var(--text-primary,#17181c); }
        .rv-sub { font-size:.86rem; color:var(--text-secondary,#666b75); margin-top:4px; }
        .rv-table-wrap { overflow-x:auto; }
        table.rv-table { width:100%; border-collapse:collapse; font-size:.88rem; }
        .rv-table thead th { text-align:left; padding:12px 16px; background:#f6f7fb; color:var(--text-secondary,#666b75); font-weight:700; text-transform:uppercase; font-size:.72rem; letter-spacing:.04em; white-space:nowrap; }
        .rv-table tbody td { padding:11px 16px; border-top:1px solid var(--border,#eef0f6); color:var(--text-primary,#17181c); }
        .rv-table tbody tr:hover { background:#fafbff; }
        .rv-table tfoot td { padding:13px 16px; border-top:2px solid var(--border,#e7e9ef); font-weight:800; background:#f9fafe; }
        .rv-num { text-align:right; white-space:nowrap; font-variant-numeric:tabular-nums; }
        .rv-empty { padding:40px 16px; text-align:center; color:var(--text-secondary,#666b75); }
    </style>
@endsection

@section('content')
@php
    // Detectar columnas numéricas (las que NO son la primera de texto) para alinear a la derecha
    $numCols = [];
    foreach ($reporte['columnas'] as $i => $col) {
        $numCols[$i] = $i > 0 && (str_contains($col, '(') || str_contains($col, '%') || $col === 'Monto' || $col === 'Neto');
    }
@endphp
<div class="rv-page">
    <div class="rv-toolbar">
        <a href="{{ route('finanzas.reportes') }}" class="btn btn-secondary">
            <svg viewBox="0 0 24 24"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20z"/></svg>
            Volver a reportes
        </a>
        <a href="{{ route('finanzas.reportes.excel', array_merge(['tipo' => $tipo], $filtros)) }}" class="btn btn-secondary">Exportar Excel</a>
        <a href="{{ route('finanzas.reportes.pdf', array_merge(['tipo' => $tipo], $filtros)) }}" class="btn btn-primary">Exportar PDF</a>
    </div>

    <div class="rv-card">
        <div class="rv-head">
            <div class="rv-title">{{ $reporte['titulo'] }}</div>
            <div class="rv-sub">{{ $reporte['subtitulo'] }}</div>
        </div>

        @if (empty($reporte['filas']))
            <div class="rv-empty">No hay datos para los filtros seleccionados.</div>
        @else
            <div class="rv-table-wrap">
                <table class="rv-table">
                    <thead>
                        <tr>
                            @foreach ($reporte['columnas'] as $i => $col)
                                <th class="{{ $numCols[$i] ? 'rv-num' : '' }}">{{ $col }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($reporte['filas'] as $fila)
                            <tr>
                                @foreach ($fila as $i => $celda)
                                    <td class="{{ $numCols[$i] ?? false ? 'rv-num' : '' }}">{{ $celda }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                    @if (! empty($reporte['totales']))
                        <tfoot>
                            <tr>
                                @foreach ($reporte['totales'] as $i => $celda)
                                    <td class="{{ $numCols[$i] ?? false ? 'rv-num' : '' }}">{{ $celda }}</td>
                                @endforeach
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        @endif
    </div>
</div>

@if (! empty($imprimir))
    @section('scripts')
        <script>window.addEventListener('load', function () { window.print(); });</script>
    @endsection
@endif
@endsection

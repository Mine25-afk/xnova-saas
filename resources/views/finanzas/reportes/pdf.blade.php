<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; color: #17181c; font-size: 11px; }
        .pdf-head { border-bottom: 2px solid #4461f2; padding-bottom: 10px; margin-bottom: 16px; }
        .pdf-brand { font-size: 13px; font-weight: bold; color: #4461f2; letter-spacing: 1px; }
        .pdf-title { font-size: 19px; font-weight: bold; margin-top: 6px; }
        .pdf-sub { font-size: 11px; color: #666b75; margin-top: 3px; }
        .pdf-meta { font-size: 9px; color: #9aa0ab; margin-top: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        thead th { background: #4461f2; color: #fff; text-align: left; padding: 7px 9px; font-size: 9.5px; text-transform: uppercase; }
        tbody td { padding: 6px 9px; border-bottom: 1px solid #e7e9ef; font-size: 10px; }
        tbody tr:nth-child(even) td { background: #f6f7fb; }
        tfoot td { padding: 8px 9px; border-top: 2px solid #4461f2; font-weight: bold; font-size: 10.5px; background: #eef1fe; }
        .num { text-align: right; }
        .pdf-foot { margin-top: 18px; font-size: 8px; color: #9aa0ab; text-align: center; }
        .pdf-empty { padding: 30px; text-align: center; color: #666b75; }
    </style>
</head>
<body>
    @php
        $numCols = [];
        foreach ($reporte['columnas'] as $i => $col) {
            $numCols[$i] = $i > 0 && (str_contains($col, '(') || str_contains($col, '%') || $col === 'Monto' || $col === 'Neto');
        }
    @endphp

    <div class="pdf-head">
        <div class="pdf-brand">KIVO</div>
        <div class="pdf-title">{{ $reporte['titulo'] }}</div>
        <div class="pdf-sub">{{ $reporte['subtitulo'] }}</div>
        <div class="pdf-meta">Generado el {{ now()->format('d/m/Y h:i A') }}</div>
    </div>

    @if (empty($reporte['filas']))
        <div class="pdf-empty">No hay datos para los filtros seleccionados.</div>
    @else
        <table>
            <thead>
                <tr>
                    @foreach ($reporte['columnas'] as $i => $col)
                        <th class="{{ $numCols[$i] ? 'num' : '' }}">{{ $col }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($reporte['filas'] as $fila)
                    <tr>
                        @foreach ($fila as $i => $celda)
                            <td class="{{ $numCols[$i] ?? false ? 'num' : '' }}">{{ $celda }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
            @if (! empty($reporte['totales']))
                <tfoot>
                    <tr>
                        @foreach ($reporte['totales'] as $i => $celda)
                            <td class="{{ $numCols[$i] ?? false ? 'num' : '' }}">{{ $celda }}</td>
                        @endforeach
                    </tr>
                </tfoot>
            @endif
        </table>
    @endif

    <div class="pdf-foot">xNova · Reporte financiero generado automáticamente</div>
</body>
</html>

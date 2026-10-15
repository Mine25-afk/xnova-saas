<?php

namespace App\Http\Controllers\Finanzas;

use App\Http\Requests\Finanzas\ReporteRequest;
use App\Models\FinanzasMovimiento;
use App\Repositories\Finanzas\CuentaRepository;
use App\Repositories\Finanzas\PropietarioRepository;
use App\Services\Finanzas\ReporteFinanzasService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReporteController extends BaseFinanzasController
{
    public function __construct(private readonly ReporteFinanzasService $reportes) {}

    public function index(CuentaRepository $cuentas, PropietarioRepository $propietarios): View
    {
        return view('finanzas.reportes', [
            'reportes' => $this->reportes->definiciones(),
            'cuentas' => $cuentas->activas(),
            'propietarios' => $propietarios->activos(),
            'tiposMovimiento' => FinanzasMovimiento::TIPOS,
        ]);
    }

    public function ver(ReporteRequest $request, string $tipo): View
    {
        return view('finanzas.reportes.ver', [
            'tipo' => $tipo,
            'reporte' => $this->reportes->construir($tipo, $request->filtros()),
            'filtros' => $request->only(['fecha_inicio', 'fecha_fin', 'cuenta', 'propietario', 'tipo']),
            'imprimir' => $request->boolean('print'),
        ]);
    }

    public function excel(ReporteRequest $request, string $tipo): StreamedResponse
    {
        $reporte = $this->reportes->construir($tipo, $request->filtros());

        return response()->streamDownload(function () use ($reporte): void {
            $salida = fopen('php://output', 'w');

            if ($salida === false) {
                return;
            }

            // BOM UTF-8 para que Excel muestre bien los acentos.
            fwrite($salida, "\xEF\xBB\xBF");
            fputcsv($salida, [$reporte['titulo']]);
            fputcsv($salida, [$reporte['subtitulo']]);
            fputcsv($salida, []);
            fputcsv($salida, $reporte['columnas']);

            foreach ($reporte['filas'] as $fila) {
                fputcsv($salida, $fila);
            }

            if ($reporte['totales'] !== []) {
                fputcsv($salida, []);
                fputcsv($salida, $reporte['totales']);
            }

            fclose($salida);
        }, $tipo.'-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function pdf(ReporteRequest $request, string $tipo): Response
    {
        $reporte = $this->reportes->construir($tipo, $request->filtros());
        $orientacion = $tipo === ReporteFinanzasService::LIBRO_MOVIMIENTOS ? 'landscape' : 'portrait';

        return Pdf::loadView('finanzas.reportes.pdf', ['reporte' => $reporte])
            ->setPaper('a4', $orientacion)
            ->download($tipo.'-'.now()->format('Y-m-d').'.pdf');
    }
}

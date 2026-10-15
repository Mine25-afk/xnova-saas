<?php

namespace App\Http\Controllers\Finanzas;

use App\Http\Requests\Finanzas\CapturaRapidaRequest;
use App\Services\Finanzas\CapturaRapidaService;
use Illuminate\Http\JsonResponse;

/**
 * Captura rápida (botón flotante global): devuelve JSON para que el modal no recargue la página.
 */
class CapturaRapidaController extends BaseFinanzasController
{
    public function __construct(private readonly CapturaRapidaService $captura) {}

    public function opciones(): JsonResponse
    {
        return response()->json($this->captura->opciones());
    }

    public function store(CapturaRapidaRequest $request): JsonResponse
    {
        $subcuentaId = $request->filled('finanzas_subcuenta_id') ? $request->integer('finanzas_subcuenta_id') : null;
        $fecha = $request->filled('fecha') ? (string) $request->validated('fecha') : null;

        return response()->json($this->captura->registrar(
            tipo: (string) $request->validated('tipo'),
            cuentaId: $request->integer('finanzas_cuenta_id'),
            subcuentaId: $subcuentaId,
            monto: (float) $request->validated('monto'),
            descripcion: $request->validated('descripcion'),
            categoria: $request->validated('categoria'),
            fecha: $fecha,
        ));
    }
}

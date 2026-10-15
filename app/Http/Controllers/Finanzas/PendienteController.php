<?php

namespace App\Http\Controllers\Finanzas;

use App\Http\Requests\Finanzas\CancelarPendienteRequest;
use App\Models\FinanzasMovimiento;
use App\Services\Finanzas\MovimientoService;
use App\Services\Finanzas\PendientesService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class PendienteController extends BaseFinanzasController
{
    public function __construct(private readonly MovimientoService $movimientos) {}

    public function index(PendientesService $pendientes): View
    {
        return view('finanzas.pendientes.index', [
            'pendientes' => $pendientes->listado(),
            'confirmarUrlTemplate' => route('finanzas.pendientes.confirmar', 'MOV_ID'),
            'anularUrlTemplate' => route('finanzas.pendientes.anular', 'MOV_ID'),
        ]);
    }

    public function confirmar(FinanzasMovimiento $movimiento): JsonResponse
    {
        $this->authorize('update', $movimiento);

        $this->movimientos->confirmarPendiente($movimiento);

        return response()->json(['message' => 'Operación confirmada.']);
    }

    public function anular(CancelarPendienteRequest $request, FinanzasMovimiento $movimiento): JsonResponse
    {
        $this->authorize('update', $movimiento);

        $this->movimientos->cancelarPendiente($movimiento, $request->motivo());

        return response()->json(['message' => 'Pendiente cancelado.']);
    }
}

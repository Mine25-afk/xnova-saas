<?php

namespace App\Http\Controllers\Finanzas;

use App\Http\Requests\Finanzas\AnularMovimientoRequest;
use App\Http\Requests\Finanzas\GastoBolsilloRequest;
use App\Http\Requests\Finanzas\IngresoBolsilloRequest;
use App\Http\Requests\Finanzas\MovimientoBolsilloRequest;
use App\Http\Requests\Finanzas\PagoDeudaBolsilloRequest;
use App\Http\Requests\Finanzas\TransferenciaRequest;
use App\Models\FinanzasCuenta;
use App\Models\FinanzasGastoFijo;
use App\Models\FinanzasIngresoFijo;
use App\Models\FinanzasMovimiento;
use App\Models\FinanzasSubcuenta;
use App\Repositories\Finanzas\DeudaRepository;
use App\Repositories\Finanzas\GastoFijoRepository;
use App\Repositories\Finanzas\IngresoFijoRepository;
use App\Services\Finanzas\DeudaService;
use App\Services\Finanzas\FijoService;
use App\Services\Finanzas\MovimientoService;
use App\Services\Finanzas\SubcuentaDetalleService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

/**
 * Detalle de un bolsillo y sus acciones rápidas (ingreso, gasto, transferencia,
 * pago de deuda y anulación).
 */
class SubcuentaController extends BaseFinanzasController
{
    public function __construct(
        private readonly MovimientoService $movimientos,
        private readonly FijoService $fijos,
    ) {}

    public function show(FinanzasCuenta $cuenta, FinanzasSubcuenta $subcuenta, SubcuentaDetalleService $detalle): View
    {
        $this->asegurarBolsilloDeCuenta($cuenta, $subcuenta);

        return view('finanzas.subcuentas.show', $detalle->paraVista($cuenta, $subcuenta, $this->puede('finanzas.deudas.pagar')));
    }

    public function ingreso(IngresoBolsilloRequest $request, FinanzasCuenta $cuenta, FinanzasSubcuenta $subcuenta, IngresoFijoRepository $ingresosFijos): JsonResponse
    {
        $this->asegurarBolsilloDeCuenta($cuenta, $subcuenta);

        $fijo = $request->fijoId() !== null ? $ingresosFijos->find($request->fijoId()) : null;

        return $this->registrarEnBolsillo($request, $cuenta, $subcuenta, FinanzasMovimiento::TIPO_INGRESO, $fijo, 'Ingreso registrado.');
    }

    public function gasto(GastoBolsilloRequest $request, FinanzasCuenta $cuenta, FinanzasSubcuenta $subcuenta, GastoFijoRepository $gastosFijos): JsonResponse
    {
        $this->asegurarBolsilloDeCuenta($cuenta, $subcuenta);

        $fijo = $request->fijoId() !== null ? $gastosFijos->find($request->fijoId()) : null;

        return $this->registrarEnBolsillo($request, $cuenta, $subcuenta, FinanzasMovimiento::TIPO_GASTO, $fijo, 'Gasto registrado.');
    }

    public function transferir(TransferenciaRequest $request, FinanzasCuenta $cuenta, FinanzasSubcuenta $subcuenta): JsonResponse
    {
        $this->asegurarBolsilloDeCuenta($cuenta, $subcuenta);

        $data = $request->toDto();
        $this->movimientos->transferir($cuenta, $subcuenta, $data);

        return response()->json([
            'message' => $data->pendiente ? 'Transferencia dejada como pendiente.' : 'Transferencia realizada.',
            'pendiente' => $data->pendiente,
        ]);
    }

    public function pagarDeuda(PagoDeudaBolsilloRequest $request, FinanzasCuenta $cuenta, FinanzasSubcuenta $subcuenta, DeudaRepository $deudas, DeudaService $deudaService): JsonResponse
    {
        $this->asegurarBolsilloDeCuenta($cuenta, $subcuenta);

        $deuda = $deudas->findOrFail($request->deudaId());
        $resultado = $deudaService->registrarPago($deuda, $subcuenta, $request->monto(), $request->pendiente());

        return response()->json($resultado);
    }

    public function anular(AnularMovimientoRequest $request, FinanzasCuenta $cuenta, FinanzasSubcuenta $subcuenta, FinanzasMovimiento $movimiento): JsonResponse
    {
        $this->asegurarBolsilloDeCuenta($cuenta, $subcuenta);
        $this->authorize('update', $movimiento);

        $perteneceAlBolsillo = (int) $movimiento->finanzas_subcuenta_id === (int) $subcuenta->id
            || (int) $movimiento->finanzas_subcuenta_destino_id === (int) $subcuenta->id;

        abort_unless($perteneceAlBolsillo, 404);

        $this->movimientos->anular($movimiento, $request->motivo());

        return response()->json(['message' => 'Movimiento anulado.']);
    }

    private function registrarEnBolsillo(
        MovimientoBolsilloRequest $request,
        FinanzasCuenta $cuenta,
        FinanzasSubcuenta $subcuenta,
        string $tipo,
        FinanzasIngresoFijo|FinanzasGastoFijo|null $fijo,
        string $mensaje,
    ): JsonResponse {
        $this->movimientos->registrarEnBolsillo($cuenta, $subcuenta, $tipo, $request->descripcion(), $request->monto());

        return response()->json(['message' => $mensaje] + $this->fijos->descontarUso($fijo, $request->monto()));
    }
}

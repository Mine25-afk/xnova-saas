<?php

namespace App\Http\Controllers\Finanzas;

use App\Http\Requests\Finanzas\IngresoFijoRequest;
use App\Http\Requests\ListadoRequest;
use App\Models\FinanzasIngresoFijo;
use App\Repositories\Finanzas\CuentaRepository;
use App\Repositories\Finanzas\IngresoFijoRepository;
use App\Repositories\Finanzas\PropietarioRepository;
use App\Services\Finanzas\FijoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class IngresoFijoController extends BaseFinanzasController
{
    public function __construct(
        private readonly IngresoFijoRepository $ingresos,
        private readonly PropietarioRepository $propietarios,
        private readonly CuentaRepository $cuentas,
        private readonly FijoService $fijos,
    ) {}

    public function index(): View
    {
        return view('finanzas.ingresos-fijos.index', [
            'propietarios' => $this->propietarios->activos(),
        ]);
    }

    public function data(ListadoRequest $request): JsonResponse
    {
        $consulta = fn () => $this->ingresos->buscar($request->busqueda(), $request->propietarioId());

        $ingresos = $this->ingresos->paginate($consulta()->orderByDesc('id'), $request->porPagina());
        $total = (float) $consulta()->sum('monto');

        return response()->json($this->listado('finanzas.ingresos-fijos.partials', 'ingresos', $ingresos) + [
            'total_monto' => $total,
            'total_label' => $this->monedaEmpresa().' '.number_format($total, 2),
        ]);
    }

    public function create(): View
    {
        return view('finanzas.ingresos-fijos.form', [
            'ingreso' => new FinanzasIngresoFijo(['active' => true, 'frecuencia' => 'mensual']),
            'isEditing' => false,
            'propietarios' => $this->propietarios->activos(),
            'cuentas' => $this->cuentas->activas(),
        ]);
    }

    public function store(IngresoFijoRequest $request): RedirectResponse
    {
        $this->ingresos->create($request->atributos() + [
            'saldo_restante' => $this->fijos->saldoInicial($request->preguntarAlUsar(), $request->monto()),
        ]);

        return redirect()->route('finanzas.ingresos-fijos.create')->with('status', 'Ingreso fijo guardado correctamente.');
    }

    public function show(FinanzasIngresoFijo $ingreso): View
    {
        $this->authorize('view', $ingreso);

        return view('finanzas.ingresos-fijos.show', [
            'ingreso' => $ingreso->load(['propietario:id,nombre', 'cuenta:id,nombre']),
        ]);
    }

    public function edit(FinanzasIngresoFijo $ingreso): View
    {
        $this->authorize('update', $ingreso);

        return view('finanzas.ingresos-fijos.form', [
            'ingreso' => $ingreso,
            'isEditing' => true,
            'propietarios' => $this->propietarios->activos(),
            'cuentas' => $this->cuentas->activas(),
        ]);
    }

    public function update(IngresoFijoRequest $request, FinanzasIngresoFijo $ingreso): RedirectResponse
    {
        $this->authorize('update', $ingreso);

        $this->ingresos->update($ingreso, array_merge(
            $request->atributos(),
            $this->fijos->saldoAlActualizar($ingreso, $request->preguntarAlUsar(), $request->monto()),
        ));

        return redirect()->route('finanzas.ingresos-fijos.show', $ingreso)->with('status', 'Ingreso fijo actualizado correctamente.');
    }
}

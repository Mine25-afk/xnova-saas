<?php

namespace App\Http\Controllers\Finanzas;

use App\Http\Requests\Finanzas\GastoFijoRequest;
use App\Http\Requests\ListadoRequest;
use App\Models\FinanzasGastoFijo;
use App\Repositories\Finanzas\GastoFijoRepository;
use App\Repositories\Finanzas\PropietarioRepository;
use App\Services\Finanzas\FijoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class GastoFijoController extends BaseFinanzasController
{
    public function __construct(
        private readonly GastoFijoRepository $gastos,
        private readonly PropietarioRepository $propietarios,
        private readonly FijoService $fijos,
    ) {}

    public function index(): View
    {
        return view('finanzas.gastos-fijos.index', [
            'propietarios' => $this->propietarios->activos(),
        ]);
    }

    public function data(ListadoRequest $request): JsonResponse
    {
        $consulta = fn () => $this->gastos->buscar($request->busqueda(), $request->propietarioId());

        $gastos = $this->gastos->paginate($consulta()->orderByDesc('id'), $request->porPagina());
        $total = (float) $consulta()->sum('monto');

        return response()->json($this->listado('finanzas.gastos-fijos.partials', 'gastos', $gastos) + [
            'total_monto' => $total,
            'total_label' => $this->monedaEmpresa().' '.number_format($total, 2),
        ]);
    }

    public function create(): View
    {
        return view('finanzas.gastos-fijos.form', [
            'gasto' => new FinanzasGastoFijo(['active' => true, 'frecuencia' => 'mensual']),
            'isEditing' => false,
            'propietarios' => $this->propietarios->activos(),
        ]);
    }

    public function store(GastoFijoRequest $request): RedirectResponse
    {
        $this->gastos->create($request->atributos() + [
            'saldo_restante' => $this->fijos->saldoInicial($request->preguntarAlUsar(), $request->monto()),
        ]);

        return redirect()->route('finanzas.gastos-fijos.create')->with('status', 'Gasto fijo guardado correctamente.');
    }

    public function show(FinanzasGastoFijo $gasto): View
    {
        $this->authorize('view', $gasto);

        return view('finanzas.gastos-fijos.show', [
            'gasto' => $gasto->load(['propietario:id,nombre']),
        ]);
    }

    public function edit(FinanzasGastoFijo $gasto): View
    {
        $this->authorize('update', $gasto);

        return view('finanzas.gastos-fijos.form', [
            'gasto' => $gasto,
            'isEditing' => true,
            'propietarios' => $this->propietarios->activos(),
        ]);
    }

    public function update(GastoFijoRequest $request, FinanzasGastoFijo $gasto): RedirectResponse
    {
        $this->authorize('update', $gasto);

        $this->gastos->update($gasto, array_merge(
            $request->atributos(),
            $this->fijos->saldoAlActualizar($gasto, $request->preguntarAlUsar(), $request->monto()),
        ));

        return redirect()->route('finanzas.gastos-fijos.show', $gasto)->with('status', 'Gasto fijo actualizado correctamente.');
    }
}

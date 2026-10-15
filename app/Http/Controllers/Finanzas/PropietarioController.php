<?php

namespace App\Http\Controllers\Finanzas;

use App\Http\Requests\Finanzas\PropietarioRequest;
use App\Http\Requests\ListadoRequest;
use App\Models\FinanzasPropietario;
use App\Repositories\Finanzas\GastoFijoRepository;
use App\Repositories\Finanzas\IngresoFijoRepository;
use App\Repositories\Finanzas\MovimientoRepository;
use App\Repositories\Finanzas\PropietarioRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

/**
 * CRUD simple: sin reglas de negocio, el controlador habla directo con el repositorio.
 */
class PropietarioController extends BaseFinanzasController
{
    public function __construct(private readonly PropietarioRepository $propietarios) {}

    public function index(): View
    {
        return view('finanzas.propietarios.index', [
            'totalPropietarios' => $this->propietarios->total(),
        ]);
    }

    public function data(ListadoRequest $request): JsonResponse
    {
        $propietarios = $this->propietarios->paginate(
            $this->propietarios->buscar($request->busqueda())->orderByDesc('id'),
            $request->porPagina(),
        );

        return response()->json($this->listado('finanzas.propietarios.partials', 'propietarios', $propietarios));
    }

    public function create(): View
    {
        return view('finanzas.propietarios.form', [
            'propietario' => new FinanzasPropietario,
            'isEditing' => false,
        ]);
    }

    public function store(PropietarioRequest $request): JsonResponse
    {
        $propietario = $this->propietarios->create($request->atributos());

        return response()->json([
            'message' => 'Propietario guardado correctamente.',
            'propietario' => $propietario,
        ], 201);
    }

    public function show(
        FinanzasPropietario $propietario,
        MovimientoRepository $movimientos,
        IngresoFijoRepository $ingresosFijos,
        GastoFijoRepository $gastosFijos,
    ): View {
        $this->authorize('view', $propietario);

        $propietario->loadCount(['cuentas']);

        return view('finanzas.propietarios.show', [
            'propietario' => $propietario,
            'movimientosCount' => $movimientos->contarDePropietario((int) $propietario->id),
            'ingresosFijosCount' => $ingresosFijos->contarDePropietario((int) $propietario->id),
            'gastosFijosCount' => $gastosFijos->contarDePropietario((int) $propietario->id),
        ]);
    }

    public function edit(FinanzasPropietario $propietario): View
    {
        $this->authorize('update', $propietario);

        return view('finanzas.propietarios.form', [
            'propietario' => $propietario,
            'isEditing' => true,
        ]);
    }

    public function update(PropietarioRequest $request, FinanzasPropietario $propietario): JsonResponse
    {
        $this->authorize('update', $propietario);

        $this->propietarios->update($propietario, $request->atributos());

        return response()->json([
            'message' => 'Propietario actualizado correctamente.',
            'propietario' => $propietario->fresh(),
        ]);
    }
}

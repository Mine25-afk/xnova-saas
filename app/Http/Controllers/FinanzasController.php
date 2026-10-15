<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesCurrentXnova;
use App\Models\FinanzasCuenta;
use App\Models\FinanzasCuentaPorCobrar;
use App\Models\FinanzasDeuda;
use App\Models\FinanzasGastoFijo;
use App\Models\FinanzasIngresoFijo;
use App\Models\FinanzasMovimiento;
use App\Models\FinanzasPropietario;
use App\Models\FinanzasSubcuenta;
use App\Services\PermissionService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FinanzasController extends Controller
{
    use ResolvesCurrentXnova;

    public function dashboard(): View
    {
        $xnovaId = $this->currentXnovaId();
        $cuentas = FinanzasCuenta::query()
            ->with('propietario:id,nombre')
            ->where('xnova_id', $xnovaId)
            ->orderBy('nombre')
            ->get();
        $balances = $this->balancesPorCuenta($xnovaId, $cuentas->pluck('id')->all());
        $monthStart = now()->startOfMonth()->toDateString();
        $monthEnd = now()->endOfMonth()->toDateString();

        $movimientosMes = FinanzasMovimiento::query()
            ->where('xnova_id', $xnovaId)
            ->whereBetween('fecha', [$monthStart, $monthEnd])
            ->get();

        $ingresosMes = (float) ($movimientosMes->where('tipo', FinanzasMovimiento::TIPO_INGRESO)->sum('monto')
            + $movimientosMes->where('tipo', FinanzasMovimiento::TIPO_AJUSTE)->sum('monto'));
        $gastosMes = (float) $movimientosMes->where('tipo', FinanzasMovimiento::TIPO_GASTO)->sum('monto');

        // Tendencia de los últimos 6 meses (ingresos vs gastos)
        $inicioTendencia = now()->copy()->startOfMonth()->subMonths(5);
        $movimientosTendencia = FinanzasMovimiento::query()
            ->where('xnova_id', $xnovaId)
            ->where('fecha', '>=', $inicioTendencia->toDateString())
            ->get(['tipo', 'monto', 'fecha']);

        $meses = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
        $tendencia = [];
        for ($i = 5; $i >= 0; $i--) {
            $ref = now()->copy()->startOfMonth()->subMonths($i);
            $clave = $ref->format('Y-m');
            $delMes = $movimientosTendencia->filter(
                fn ($mv) => \Carbon\Carbon::parse($mv->fecha)->format('Y-m') === $clave
            );
            $tendencia[] = [
                'mes' => $meses[$ref->month - 1] . ' ' . $ref->format('y'),
                'ingresos' => (float) ($delMes->whereIn('tipo', [FinanzasMovimiento::TIPO_INGRESO, FinanzasMovimiento::TIPO_AJUSTE])->sum('monto')),
                'gastos' => (float) ($delMes->where('tipo', FinanzasMovimiento::TIPO_GASTO)->sum('monto')),
            ];
        }

        // Gastos del mes agrupados por categoría (para la dona)
        $gastosCategoria = $movimientosMes
            ->where('tipo', FinanzasMovimiento::TIPO_GASTO)
            ->groupBy(fn ($mv) => filled($mv->categoria) ? $mv->categoria : 'Sin categoría')
            ->map(fn ($grupo) => (float) $grupo->sum('monto'))
            ->sortDesc()
            ->take(8);

        // Saldo por cuenta (para barras), ya calculado en $balances
        $saldoCuentas = $cuentas->map(fn ($c) => [
            'nombre' => $c->nombre,
            'saldo' => (float) ($balances[$c->id] ?? 0),
        ])->values()->all();

        return view('finanzas.dashboard', [
            'cuentas' => $cuentas,
            'balances' => $balances,
            'saldoTotal' => array_sum($balances),
            'ingresosMes' => $ingresosMes,
            'gastosMes' => $gastosMes,
            'flujoNeto' => $ingresosMes - $gastosMes,
            'margenNeto' => $ingresosMes > 0 ? (($ingresosMes - $gastosMes) / $ingresosMes) * 100 : 0,
            'ingresosFijos' => (float) FinanzasIngresoFijo::query()->where('xnova_id', $xnovaId)->where('active', true)->sum('monto'),
            'gastosFijos' => (float) FinanzasGastoFijo::query()->where('xnova_id', $xnovaId)->where('active', true)->sum('monto'),
            'tendencia' => $tendencia,
            'gastosCategoria' => $gastosCategoria,
            'saldoCuentas' => $saldoCuentas,
            'movimientosRecientes' => FinanzasMovimiento::query()
                ->with(['cuenta:id,nombre', 'cuentaDestino:id,nombre', 'propietario:id,nombre'])
                ->where('xnova_id', $xnovaId)
                ->whereNull('anulado_en')
                ->where('pendiente', false)
                ->orderByDesc('fecha')
                ->orderByDesc('id')
                ->limit(8)
                ->get(),
            'pendientesCount' => FinanzasMovimiento::query()
                ->where('xnova_id', $xnovaId)
                ->where('pendiente', true)
                ->whereNull('anulado_en')
                ->count(),
        ]);
    }

    public function propietarios(Request $request): View
    {
        $xnovaId = $this->currentXnovaId();

        return view('finanzas.propietarios.index', [
            'totalPropietarios' => FinanzasPropietario::query()->where('xnova_id', $xnovaId)->count(),
        ]);
    }

    public function propietariosData(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:50'],
        ]);

        $search = trim((string) ($validated['search'] ?? ''));
        $perPage = (int) ($validated['per_page'] ?? 10);
        $propietarios = $this->propietariosBaseQuery($search)
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        return response()->json([
            'rows' => view('finanzas.propietarios.partials.rows', ['propietarios' => $propietarios])->render(),
            'cards' => view('finanzas.propietarios.partials.cards', ['propietarios' => $propietarios])->render(),
            'pagination' => view('finanzas.propietarios.partials.pagination', ['propietarios' => $propietarios])->render(),
            'meta' => [
                'current_page' => $propietarios->currentPage(),
                'last_page' => $propietarios->lastPage(),
                'per_page' => $propietarios->perPage(),
                'total' => $propietarios->total(),
                'from' => $propietarios->firstItem(),
                'to' => $propietarios->lastItem(),
            ],
        ]);
    }

    public function createPropietario(): View
    {
        return view('finanzas.propietarios.form', [
            'propietario' => new FinanzasPropietario(),
            'isEditing' => false,
        ]);
    }

    public function showPropietario(FinanzasPropietario $propietario): View
    {
        $this->ensureCurrentXnova($propietario);

        $propietario->loadCount(['cuentas']);

        $movimientosCount = FinanzasMovimiento::query()
            ->where('xnova_id', $this->currentXnovaId())
            ->where('finanzas_propietario_id', $propietario->id)
            ->count();

        $ingresosFijosCount = FinanzasIngresoFijo::query()
            ->where('xnova_id', $this->currentXnovaId())
            ->where('finanzas_propietario_id', $propietario->id)
            ->count();

        $gastosFijosCount = FinanzasGastoFijo::query()
            ->where('xnova_id', $this->currentXnovaId())
            ->where('finanzas_propietario_id', $propietario->id)
            ->count();

        return view('finanzas.propietarios.show', [
            'propietario' => $propietario,
            'movimientosCount' => $movimientosCount,
            'ingresosFijosCount' => $ingresosFijosCount,
            'gastosFijosCount' => $gastosFijosCount,
        ]);
    }

    public function editPropietario(FinanzasPropietario $propietario): View
    {
        $this->ensureCurrentXnova($propietario);

        return view('finanzas.propietarios.form', [
            'propietario' => $propietario,
            'isEditing' => true,
        ]);
    }

    public function storePropietario(Request $request): JsonResponse
    {
        $propietario = FinanzasPropietario::query()->create($this->validatePropietario($request));

        return response()->json([
            'message' => 'Propietario guardado correctamente.',
            'propietario' => $propietario,
        ], 201);
    }

    public function updatePropietario(Request $request, FinanzasPropietario $propietario): JsonResponse
    {
        $this->ensureCurrentXnova($propietario);
        $propietario->update($this->validatePropietario($request, $propietario));

        return response()->json([
            'message' => 'Propietario actualizado correctamente.',
            'propietario' => $propietario->fresh(),
        ]);
    }

    public function cuentas(): View
    {
        $xnovaId = $this->currentXnovaId();

        return view('finanzas.cuentas.index', [
            'propietarios' => $this->propietariosActivos($xnovaId),
        ]);
    }

    public function cuentasData(Request $request): JsonResponse
    {
        $validated = $this->validateListRequest($request);
        $search = trim((string) ($validated['search'] ?? ''));
        $perPage = (int) ($validated['per_page'] ?? 10);
        $propietarioId = $request->filled('propietario_id') ? (int) $request->input('propietario_id') : null;
        $xnovaId = $this->currentXnovaId();

        $cuentas = $this->cuentasBaseQuery($search, $propietarioId)
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
        $balances = $this->balancesPorCuenta($xnovaId, $cuentas->pluck('id')->all());

        // Totales por moneda (sumando todas las cuentas filtradas, no solo la página).
        $cuentasFiltradas = $this->cuentasBaseQuery($search, $propietarioId)
            ->get(['id', 'moneda']);
        $balancesTodos = $this->balancesPorCuenta($xnovaId, $cuentasFiltradas->pluck('id')->all());
        $totalesPorMoneda = [];
        foreach ($cuentasFiltradas as $c) {
            $moneda = $c->moneda ?: 'LPS';
            $totalesPorMoneda[$moneda] = ($totalesPorMoneda[$moneda] ?? 0) + (float) ($balancesTodos[$c->id] ?? 0);
        }
        ksort($totalesPorMoneda);

        $totalesFmt = [];
        foreach ($totalesPorMoneda as $moneda => $monto) {
            $totalesFmt[] = ['moneda' => $moneda, 'monto' => $monto, 'label' => $moneda.' '.number_format($monto, 2)];
        }

        return response()->json([
            'rows' => view('finanzas.cuentas.partials.rows', ['cuentas' => $cuentas, 'balances' => $balances])->render(),
            'cards' => view('finanzas.cuentas.partials.cards', ['cuentas' => $cuentas, 'balances' => $balances])->render(),
            'pagination' => view('finanzas.cuentas.partials.pagination', ['cuentas' => $cuentas])->render(),
            'meta' => $this->paginationMeta($cuentas),
            'totales' => $totalesFmt,
        ]);
    }

    public function createCuenta(): View
    {
        $xnovaId = $this->currentXnovaId();

        return view('finanzas.cuentas.form', [
            'cuenta' => new FinanzasCuenta(['active' => true, 'moneda' => $this->monedaDefault()]),
            'isEditing' => false,
            'propietarios' => $this->propietariosActivos($xnovaId),
        ]);
    }

    public function storeCuenta(Request $request): RedirectResponse
    {
        $data = $this->validateCuenta($request);
        $saldoInicial = (float) ($data['saldo_inicial'] ?? 0);
        unset($data['saldo_inicial']);

        $cuenta = FinanzasCuenta::query()->create($data);
        $general = $this->generalSubcuenta($cuenta);

        if ($saldoInicial !== 0.0) {
            FinanzasMovimiento::query()->create([
                'xnova_id' => $data['xnova_id'],
                'finanzas_cuenta_id' => $cuenta->id,
                'finanzas_subcuenta_id' => $general->id,
                'finanzas_propietario_id' => $cuenta->finanzas_propietario_id,
                'tipo' => $saldoInicial > 0 ? FinanzasMovimiento::TIPO_AJUSTE : FinanzasMovimiento::TIPO_GASTO,
                'descripcion' => $saldoInicial > 0 ? 'Saldo inicial' : 'Saldo inicial negativo',
                'categoria' => 'Saldo inicial',
                'monto' => abs($saldoInicial),
                'fecha' => now()->toDateString(),
            ]);
        }

        return redirect()->route('finanzas.cuentas.create')->with('status', 'Cuenta guardada correctamente.');
    }

    public function showCuenta(FinanzasCuenta $cuenta): View
    {
        $this->ensureCurrentXnova($cuenta);
        $this->generalSubcuenta($cuenta);

        $cuenta->load(['propietario:id,nombre', 'subcuentas' => fn ($query) => $query->orderByDesc('es_general')->orderBy('nombre')]);
        $balances = $this->balancesPorSubcuenta($this->currentXnovaId(), $cuenta->subcuentas->pluck('id')->all());

        // Saldo total = balance real de ESTA cuenta (no suma de otras cuentas).
        $balancesCuenta = $this->balancesPorCuenta($this->currentXnovaId(), [$cuenta->id]);
        $saldoTotal = (float) ($balancesCuenta[$cuenta->id] ?? 0);

        $editingBolsillo = $this->editingRecord(FinanzasSubcuenta::class, (int) request()->integer('edit_bolsillo'));

        if ($editingBolsillo && (int) $editingBolsillo->finanzas_cuenta_id !== (int) $cuenta->id) {
            $editingBolsillo = null;
        }

        return view('finanzas.cuentas.show', [
            'cuenta' => $cuenta,
            'balances' => $balances,
            'saldoTotal' => $saldoTotal,
            'editingBolsillo' => $editingBolsillo,
            'movimientosRecientes' => FinanzasMovimiento::query()
                ->with(['subcuenta:id,nombre', 'subcuentaDestino:id,nombre'])
                ->where('xnova_id', $this->currentXnovaId())
                ->where(function ($query) use ($cuenta): void {
                    $query->where('finanzas_cuenta_id', $cuenta->id)
                        ->orWhere('finanzas_cuenta_destino_id', $cuenta->id);
                })
                ->orderByDesc('fecha')
                ->orderByDesc('id')
                ->limit(8)
                ->get(),
        ]);
    }

    public function editCuenta(FinanzasCuenta $cuenta): View
    {
        $this->ensureCurrentXnova($cuenta);
        $xnovaId = $this->currentXnovaId();

        return view('finanzas.cuentas.form', [
            'cuenta' => $cuenta,
            'isEditing' => true,
            'propietarios' => $this->propietariosActivos($xnovaId),
        ]);
    }

    public function updateCuenta(Request $request, FinanzasCuenta $cuenta): RedirectResponse
    {
        $this->ensureCurrentXnova($cuenta);
        $data = $this->validateCuenta($request, $cuenta);
        unset($data['saldo_inicial']);
        $cuenta->update($data);

        return redirect()->route('finanzas.cuentas.show', $cuenta)->with('status', 'Cuenta actualizada correctamente.');
    }

    public function createBolsillo(FinanzasCuenta $cuenta): View
    {
        $this->ensureCurrentXnova($cuenta);

        return view('finanzas.cuentas.bolsillo-form', [
            'cuenta' => $cuenta,
            'bolsillo' => new FinanzasSubcuenta(['active' => true]),
            'isEditing' => false,
        ]);
    }

    public function storeBolsillo(Request $request, FinanzasCuenta $cuenta): RedirectResponse
    {
        $this->ensureCurrentXnova($cuenta);

        FinanzasSubcuenta::query()->create($this->validateBolsillo($request, $cuenta));

        return redirect()->route('finanzas.cuentas.show', $cuenta)->with('status', 'Bolsillo guardado correctamente.');
    }

    public function editBolsillo(FinanzasCuenta $cuenta, FinanzasSubcuenta $bolsillo): View
    {
        $this->ensureBolsilloBelongsToCuenta($cuenta, $bolsillo);

        return view('finanzas.cuentas.bolsillo-form', [
            'cuenta' => $cuenta,
            'bolsillo' => $bolsillo,
            'isEditing' => true,
        ]);
    }

    public function updateBolsillo(Request $request, FinanzasCuenta $cuenta, FinanzasSubcuenta $bolsillo): RedirectResponse
    {
        $this->ensureBolsilloBelongsToCuenta($cuenta, $bolsillo);
        $bolsillo->update($this->validateBolsillo($request, $cuenta, $bolsillo));

        return redirect()->route('finanzas.cuentas.show', $cuenta)->with('status', 'Bolsillo actualizado correctamente.');
    }

    public function deactivateBolsillo(FinanzasCuenta $cuenta, FinanzasSubcuenta $bolsillo): RedirectResponse
    {
        $this->ensureBolsilloBelongsToCuenta($cuenta, $bolsillo);

        if ($bolsillo->es_general) {
            return back()->withErrors(['bolsillo' => 'El bolsillo general no se puede desactivar.']);
        }

        $bolsillo->update(['active' => false]);

        return redirect()->route('finanzas.cuentas.show', $cuenta)->with('status', 'Bolsillo desactivado correctamente.');
    }

    public function distributeBolsillos(Request $request, FinanzasCuenta $cuenta): RedirectResponse
    {
        $this->ensureCurrentXnova($cuenta);
        $xnovaId = $this->currentXnovaId();
        $request->merge([
            'destinos' => collect($request->input('destinos', []))
                ->filter(fn ($destino) => filled($destino['finanzas_subcuenta_destino_id'] ?? null) || filled($destino['monto'] ?? null))
                ->values()
                ->all(),
        ]);

        $validated = $request->validate([
            'finanzas_subcuenta_id' => ['required', 'integer', Rule::exists('finanzas_subcuentas', 'id')->where('xnova_id', $xnovaId)->where('finanzas_cuenta_id', $cuenta->id)->where('active', true)],
            'fecha' => ['required', 'date'],
            'destinos' => ['required', 'array', 'min:1'],
            'destinos.*.finanzas_subcuenta_destino_id' => ['required', 'integer', Rule::exists('finanzas_subcuentas', 'id')->where('xnova_id', $xnovaId)->where('finanzas_cuenta_id', $cuenta->id)->where('active', true)],
            'destinos.*.monto' => ['required', 'numeric', 'min:0.01'],
        ]);

        $origenId = (int) $validated['finanzas_subcuenta_id'];
        $destinoIds = collect($validated['destinos'])->pluck('finanzas_subcuenta_destino_id')->map(fn ($id) => (int) $id)->all();

        if (in_array($origenId, $destinoIds, true)) {
            return back()->withInput()->withErrors(['destinos' => 'Un bolsillo destino no puede ser igual al bolsillo origen.']);
        }

        $destinos = FinanzasSubcuenta::query()
            ->where('xnova_id', $xnovaId)
            ->where('finanzas_cuenta_id', $cuenta->id)
            ->whereIn('id', $destinoIds)
            ->pluck('nombre', 'id');

        foreach ($validated['destinos'] as $destino) {
            $destinoId = (int) $destino['finanzas_subcuenta_destino_id'];

            FinanzasMovimiento::query()->create([
                'xnova_id' => $xnovaId,
                'finanzas_cuenta_id' => $cuenta->id,
                'finanzas_cuenta_destino_id' => $cuenta->id,
                'finanzas_subcuenta_id' => $origenId,
                'finanzas_subcuenta_destino_id' => $destinoId,
                'finanzas_propietario_id' => $cuenta->finanzas_propietario_id,
                'tipo' => FinanzasMovimiento::TIPO_TRANSFERENCIA,
                'descripcion' => 'Distribucion a '.($destinos[$destinoId] ?? 'bolsillo'),
                'categoria' => 'Distribucion',
                'monto' => $destino['monto'],
                'fecha' => $validated['fecha'],
            ]);
        }

        return redirect()->route('finanzas.cuentas.show', $cuenta)->with('status', 'Distribucion guardada correctamente.');
    }

    /**
     * Ajusta el saldo real de la cuenta repartiendo la diferencia entre bolsillos.
     * La diferencia (saldo_real - saldo_actual) puede ser positiva (agregar) o
     * negativa (descontar). Los montos asignados se reciben como magnitudes
     * positivas; el signo lo define la diferencia. La suma asignada no puede
     * exceder la diferencia.
     */
    public function ajustarCuenta(Request $request, FinanzasCuenta $cuenta): RedirectResponse
    {
        $this->ensureCurrentXnova($cuenta);
        $xnovaId = $this->currentXnovaId();

        $data = $request->validate([
            'saldo_real' => ['required', 'numeric'],
            'ajustes' => ['required', 'array', 'min:1'],
            'ajustes.*.finanzas_subcuenta_id' => ['required', 'integer', Rule::exists('finanzas_subcuentas', 'id')->where('xnova_id', $xnovaId)->where('finanzas_cuenta_id', $cuenta->id)->where('active', true)],
            'ajustes.*.monto' => ['nullable', 'numeric', 'min:0'],
        ], [], [
            'saldo_real' => 'saldo real',
        ]);

        $saldoActual = (float) ($this->balancesPorCuenta($xnovaId, [$cuenta->id])[$cuenta->id] ?? 0);
        $diferencia = round((float) $data['saldo_real'] - $saldoActual, 2);

        if (abs($diferencia) < 0.01) {
            return redirect()->route('finanzas.cuentas.show', $cuenta)
                ->with('status', 'El saldo real coincide con el actual, no hay nada que ajustar.');
        }

        $signo = $diferencia > 0 ? 1 : -1;

        // Solo bolsillos con monto asignado mayor a 0.
        $asignaciones = collect($data['ajustes'])
            ->map(fn ($a) => ['id' => (int) $a['finanzas_subcuenta_id'], 'monto' => round((float) ($a['monto'] ?? 0), 2)])
            ->filter(fn ($a) => $a['monto'] > 0)
            ->values();

        if ($asignaciones->isEmpty()) {
            return redirect()->route('finanzas.cuentas.show', $cuenta)->withInput()
                ->withErrors(['ajustes' => 'Asigna al menos un monto a algun bolsillo.']);
        }

        $totalAsignado = round((float) $asignaciones->sum('monto'), 2);
        if ($totalAsignado > abs($diferencia) + 0.001) {
            return redirect()->route('finanzas.cuentas.show', $cuenta)->withInput()
                ->withErrors(['ajustes' => 'La suma asignada (' . number_format($totalAsignado, 2) . ') excede la diferencia de ' . number_format(abs($diferencia), 2) . '.']);
        }

        // En ajustes negativos, no permitir dejar un bolsillo con saldo negativo.
        $balancesSub = $this->balancesPorSubcuenta($xnovaId, $asignaciones->pluck('id')->all());
        if ($signo < 0) {
            foreach ($asignaciones as $a) {
                $saldoBolsillo = (float) ($balancesSub[$a['id']] ?? 0);
                if ($a['monto'] > $saldoBolsillo + 0.001) {
                    return redirect()->route('finanzas.cuentas.show', $cuenta)->withInput()
                        ->withErrors(['ajustes' => 'No puedes descontar mas de lo que tiene un bolsillo. Revisa los montos.']);
                }
            }
        }

        DB::transaction(function () use ($cuenta, $xnovaId, $asignaciones, $signo): void {
            foreach ($asignaciones as $a) {
                FinanzasMovimiento::query()->create([
                    'xnova_id' => $xnovaId,
                    'finanzas_cuenta_id' => $cuenta->id,
                    'finanzas_subcuenta_id' => $a['id'],
                    'finanzas_propietario_id' => $cuenta->finanzas_propietario_id,
                    'tipo' => FinanzasMovimiento::TIPO_AJUSTE,
                    'descripcion' => $signo > 0 ? 'Ajuste de saldo (suma)' : 'Ajuste de saldo (resta)',
                    'categoria' => 'Ajuste',
                    'monto' => $signo * $a['monto'],
                    'fecha' => now()->toDateString(),
                ]);
            }
        });

        return redirect()->route('finanzas.cuentas.show', $cuenta)
            ->with('status', 'Saldo ajustado correctamente.');
    }

    // ===== Subcuenta detail (card-based action flow) =====

    public function showSubcuenta(FinanzasCuenta $cuenta, FinanzasSubcuenta $subcuenta): View
    {
        $this->ensureBolsilloBelongsToCuenta($cuenta, $subcuenta);
        $xnovaId = $this->currentXnovaId();

        $cuenta->load('propietario:id,nombre');

        $balances = $this->balancesPorSubcuenta($xnovaId, [$subcuenta->id]);
        $saldo = (float) ($balances[$subcuenta->id] ?? 0);

        // Otras subcuentas del mismo xnova, agrupadas por cuenta, para transferencias
        $destinosTransferencia = FinanzasSubcuenta::query()
            ->with('cuenta:id,nombre,moneda')
            ->where('xnova_id', $xnovaId)
            ->where('active', true)
            ->where('id', '!=', $subcuenta->id)
            ->orderByDesc('es_general')
            ->orderBy('nombre')
            ->get()
            ->groupBy(fn ($sub) => $sub->cuenta?->nombre ?? 'Sin cuenta');

        // Estructura para el asistente de transferencia (paso 1: cuentas, paso 2: bolsillos)
        $cuentasDestino = FinanzasCuenta::query()
            ->where('xnova_id', $xnovaId)
            ->where('active', true)
            ->with([
                'propietario:id,nombre',
                'subcuentas' => function ($query) use ($subcuenta): void {
                    $query->where('active', true)
                        ->where('id', '!=', $subcuenta->id)
                        ->orderByDesc('es_general')
                        ->orderBy('nombre');
                },
            ])
            ->orderBy('nombre')
            ->get()
            ->filter(fn ($c) => $c->subcuentas->isNotEmpty())
            ->map(fn ($c) => [
                'id' => $c->id,
                'nombre' => $c->nombre,
                'tipo' => $c->tipo,
                'moneda' => $c->moneda,
                'icono' => $c->icono,
                'propietario_id' => $c->finanzas_propietario_id,
                'propietario_nombre' => $c->propietario?->nombre,
                'bolsillos' => $c->subcuentas->map(fn ($b) => [
                    'id' => $b->id,
                    'nombre' => $b->nombre,
                    'icono' => $b->icono,
                    'es_general' => (bool) $b->es_general,
                ])->values()->all(),
            ])
            ->values()
            ->all();

        $propietariosDestino = collect($cuentasDestino)
            ->map(fn ($c) => [
                'id' => $c['propietario_id'],
                'nombre' => $c['propietario_nombre'] ?? 'Sin propietario',
            ])
            ->unique(fn ($p) => (string) ($p['id'] ?? 'null'))
            ->values()
            ->all();

        // Filtra los fijos por el propietario de la cuenta (más los generales sin propietario).
        // Si la cuenta no tiene propietario, se muestran todos.
        $propietarioCuentaId = $cuenta->finanzas_propietario_id;
        $filtroPropietario = function ($query) use ($propietarioCuentaId): void {
            if ($propietarioCuentaId !== null) {
                $query->where(function ($q) use ($propietarioCuentaId): void {
                    $q->where('finanzas_propietario_id', $propietarioCuentaId)
                        ->orWhereNull('finanzas_propietario_id');
                });
            }
        };

        $ingresosFijos = FinanzasIngresoFijo::query()
            ->where('xnova_id', $xnovaId)
            ->where('active', true)
            ->tap($filtroPropietario)
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'icono', 'monto', 'preguntar_al_usar', 'saldo_restante']);

        $gastosFijos = FinanzasGastoFijo::query()
            ->where('xnova_id', $xnovaId)
            ->where('active', true)
            ->tap($filtroPropietario)
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'icono', 'monto', 'preguntar_al_usar', 'saldo_restante']);

        $deudasPendientesModelos = FinanzasDeuda::query()
            ->where('xnova_id', $xnovaId)
            ->where('active', true)
            ->where('saldo_pendiente', '>', 0)
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'icono', 'saldo_pendiente', 'fecha_vencimiento']);
        $totalDeudas = (float) $deudasPendientesModelos->sum('saldo_pendiente');
        $deudasPendientes = $deudasPendientesModelos->map(fn ($d) => [
            'id' => $d->id,
            'nombre' => $d->nombre,
            'icono' => $d->icono,
            'saldo_pendiente' => (float) $d->saldo_pendiente,
            'vencimiento' => $d->fecha_vencimiento?->format('d/m/Y'),
            'vencida' => $d->fecha_vencimiento !== null && $d->fecha_vencimiento->isPast(),
        ])->values()->all();
        $puedePagarDeuda = app(PermissionService::class)->userHasPermission(auth()->user(), 'finanzas.deudas.pagar');

        $movimientos = FinanzasMovimiento::query()
            ->with(['subcuenta:id,nombre', 'subcuentaDestino:id,nombre'])
            ->where('xnova_id', $xnovaId)
            ->where(function ($query) use ($subcuenta): void {
                $query->where('finanzas_subcuenta_id', $subcuenta->id)
                    ->orWhere('finanzas_subcuenta_destino_id', $subcuenta->id);
            })
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->limit(15)
            ->get();

        return view('finanzas.subcuentas.show', [
            'cuenta' => $cuenta,
            'subcuenta' => $subcuenta,
            'saldo' => $saldo,
            'destinosTransferencia' => $destinosTransferencia,
            'cuentasDestino' => $cuentasDestino,
            'propietariosDestino' => $propietariosDestino,
            'ingresosFijos' => $ingresosFijos,
            'gastosFijos' => $gastosFijos,
            'movimientos' => $movimientos,
            'deudasPendientes' => $deudasPendientes,
            'totalDeudas' => $totalDeudas,
            'puedePagarDeuda' => $puedePagarDeuda,
        ]);
    }

    public function storeIngresoSubcuenta(Request $request, FinanzasCuenta $cuenta, FinanzasSubcuenta $subcuenta): JsonResponse
    {
        $this->ensureBolsilloBelongsToCuenta($cuenta, $subcuenta);
        $xnovaId = $cuenta->xnova_id;

        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:180'],
            'monto' => ['required', 'numeric', 'min:0.01'],
            'finanzas_ingreso_fijo_id' => ['nullable', 'integer', Rule::exists('finanzas_ingresos_fijos', 'id')->where('xnova_id', $xnovaId)],
        ]);

        FinanzasMovimiento::query()->create([
            'xnova_id' => $xnovaId,
            'finanzas_cuenta_id' => $cuenta->id,
            'finanzas_subcuenta_id' => $subcuenta->id,
            'finanzas_propietario_id' => $cuenta->finanzas_propietario_id,
            'tipo' => FinanzasMovimiento::TIPO_INGRESO,
            'descripcion' => $data['nombre'],
            'categoria' => null,
            'monto' => $data['monto'],
            'fecha' => now()->toDateString(),
        ]);

        $payload = ['message' => 'Ingreso registrado.'];

        if (! empty($data['finanzas_ingreso_fijo_id'])) {
            $payload = array_merge($payload, $this->actualizarSaldoFijo(
                FinanzasIngresoFijo::query()->where('xnova_id', $xnovaId)->find($data['finanzas_ingreso_fijo_id']),
                (float) $data['monto']
            ));
        }

        return response()->json($payload);
    }

    public function storeGastoSubcuenta(Request $request, FinanzasCuenta $cuenta, FinanzasSubcuenta $subcuenta): JsonResponse
    {
        $this->ensureBolsilloBelongsToCuenta($cuenta, $subcuenta);
        $xnovaId = $cuenta->xnova_id;

        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:180'],
            'monto' => ['required', 'numeric', 'min:0.01'],
            'finanzas_gasto_fijo_id' => ['nullable', 'integer', Rule::exists('finanzas_gastos_fijos', 'id')->where('xnova_id', $xnovaId)],
        ]);

        FinanzasMovimiento::query()->create([
            'xnova_id' => $xnovaId,
            'finanzas_cuenta_id' => $cuenta->id,
            'finanzas_subcuenta_id' => $subcuenta->id,
            'finanzas_propietario_id' => $cuenta->finanzas_propietario_id,
            'tipo' => FinanzasMovimiento::TIPO_GASTO,
            'descripcion' => $data['nombre'],
            'categoria' => null,
            'monto' => $data['monto'],
            'fecha' => now()->toDateString(),
        ]);

        $payload = ['message' => 'Gasto registrado.'];

        if (! empty($data['finanzas_gasto_fijo_id'])) {
            $payload = array_merge($payload, $this->actualizarSaldoFijo(
                FinanzasGastoFijo::query()->where('xnova_id', $xnovaId)->find($data['finanzas_gasto_fijo_id']),
                (float) $data['monto']
            ));
        }

        return response()->json($payload);
    }

    public function storePagoDeudaSubcuenta(Request $request, FinanzasCuenta $cuenta, FinanzasSubcuenta $subcuenta): JsonResponse
    {
        $this->ensureBolsilloBelongsToCuenta($cuenta, $subcuenta);
        $xnovaId = $cuenta->xnova_id;

        $data = $request->validate([
            'finanzas_deuda_id' => ['required', 'integer', Rule::exists('finanzas_deudas', 'id')->where('xnova_id', $xnovaId)],
            'monto' => ['required', 'numeric', 'min:0.01'],
            'pendiente' => ['nullable', 'boolean'],
        ]);

        $deuda = FinanzasDeuda::query()->where('xnova_id', $xnovaId)->findOrFail($data['finanzas_deuda_id']);
        $pendiente = (bool) ($data['pendiente'] ?? false);

        // El abono no puede superar el saldo pendiente de la deuda.
        $request->validate([
            'monto' => ['max:' . (float) $deuda->saldo_pendiente],
        ], [], ['monto' => 'monto']);

        $monto = round((float) $data['monto'], 2);

        // El abono no puede superar el saldo disponible del bolsillo (se permite abono parcial).
        $saldoBolsillo = (float) ($this->balancesPorSubcuenta($xnovaId, [$subcuenta->id])[$subcuenta->id] ?? 0);
        if ($monto > $saldoBolsillo) {
            return response()->json([
                'message' => 'El bolsillo no tiene saldo suficiente. Máximo disponible: ' . $cuenta->moneda . ' ' . number_format(max(0, $saldoBolsillo), 2) . '.',
                'max' => max(0, $saldoBolsillo),
            ], 422);
        }

        DB::transaction(function () use ($cuenta, $subcuenta, $deuda, $monto, $xnovaId, $pendiente): void {
            FinanzasMovimiento::query()->create([
                'xnova_id' => $xnovaId,
                'finanzas_cuenta_id' => $cuenta->id,
                'finanzas_subcuenta_id' => $subcuenta->id,
                'finanzas_propietario_id' => $cuenta->finanzas_propietario_id,
                'finanzas_deuda_id' => $deuda->id,
                'tipo' => FinanzasMovimiento::TIPO_GASTO,
                'descripcion' => 'Pago deuda: ' . $deuda->nombre,
                'categoria' => 'Deudas',
                'monto' => $monto,
                'fecha' => now()->toDateString(),
                'pendiente' => $pendiente,
            ]);

            // Si queda pendiente, el saldo de la deuda no baja hasta confirmar.
            if (! $pendiente) {
                $deuda->decrement('saldo_pendiente', $monto);
            }
        });

        if ($pendiente) {
            return response()->json([
                'message' => 'Pago dejado como pendiente.',
                'pendiente' => true,
                'saldo_pendiente' => (float) $deuda->saldo_pendiente,
            ]);
        }

        $deuda->refresh();
        $saldoPendiente = (float) $deuda->saldo_pendiente;

        return response()->json([
            'message' => $saldoPendiente <= 0 ? 'Deuda pagada por completo.' : 'Abono registrado.',
            'saldo_pendiente' => $saldoPendiente,
            'pagada' => $saldoPendiente <= 0,
        ]);
    }

    /**
     * Rebaja el saldo restante de un ingreso/gasto fijo que tiene preguntar_al_usar=true.
     * Si el saldo cae a cero o menos, lo reinicia al monto original.
     */
    protected function actualizarSaldoFijo(?Model $fijo, float $montoUsado): array
    {
        if (! $fijo || ! $fijo->preguntar_al_usar) {
            return [];
        }

        $saldoActual = $fijo->saldo_restante !== null
            ? (float) $fijo->saldo_restante
            : (float) $fijo->monto;

        $nuevoSaldo = $saldoActual - $montoUsado;
        $reinicio = false;

        if ($nuevoSaldo <= 0) {
            $nuevoSaldo = (float) $fijo->monto;
            $reinicio = true;
        }

        $fijo->update(['saldo_restante' => $nuevoSaldo]);

        return [
            'saldo_restante' => $nuevoSaldo,
            'reiniciado' => $reinicio,
        ];
    }

    public function storeTransferenciaSubcuenta(Request $request, FinanzasCuenta $cuenta, FinanzasSubcuenta $subcuenta): JsonResponse
    {
        $this->ensureBolsilloBelongsToCuenta($cuenta, $subcuenta);
        $xnovaId = $this->currentXnovaId();

        $balances = $this->balancesPorSubcuenta($xnovaId, [$subcuenta->id]);
        $saldoOrigen = (float) ($balances[$subcuenta->id] ?? 0);

        if ($saldoOrigen <= 0) {
            return response()->json(['message' => 'Este bolsillo no tiene saldo positivo, no puedes transferir dinero.'], 422);
        }

        $data = $request->validate([
            'finanzas_subcuenta_destino_id' => ['required_without:crear_bolsillo_espejo', 'nullable', 'integer', 'different:origen', Rule::exists('finanzas_subcuentas', 'id')->where('xnova_id', $xnovaId)->where('active', true)],
            'crear_bolsillo_espejo' => ['nullable', 'boolean'],
            'finanzas_cuenta_destino_id' => ['required_if:crear_bolsillo_espejo,true', 'nullable', 'integer', Rule::exists('finanzas_cuentas', 'id')->where('xnova_id', $xnovaId)->where('active', true)],
            'monto' => ['required', 'numeric', 'min:0.01'],
            'descripcion' => ['nullable', 'string', 'max:180'],
            'pendiente' => ['nullable', 'boolean'],
        ], [
            'finanzas_subcuenta_destino_id.different' => 'El destino debe ser distinto al origen.',
            'finanzas_subcuenta_destino_id.required_without' => 'Selecciona el bolsillo destino.',
        ]);

        if (! empty($data['crear_bolsillo_espejo'])) {
            // El destino no tiene un bolsillo con este propósito: se crea (o
            // reactiva) uno con el mismo nombre para que la trazabilidad del
            // dinero viaje con la transferencia.
            $cuentaDestino = FinanzasCuenta::query()
                ->where('xnova_id', $xnovaId)
                ->findOrFail((int) $data['finanzas_cuenta_destino_id']);

            if ((int) $cuentaDestino->id === (int) $cuenta->id) {
                return response()->json(['message' => 'Este bolsillo ya existe en la cuenta seleccionada.'], 422);
            }

            $destino = FinanzasSubcuenta::query()
                ->where('xnova_id', $xnovaId)
                ->where('finanzas_cuenta_id', $cuentaDestino->id)
                ->where('nombre', $subcuenta->nombre)
                ->first();

            if ($destino && ! $destino->active) {
                $destino->update(['active' => true]);
            }

            if (! $destino) {
                $destino = FinanzasSubcuenta::query()->create([
                    'xnova_id' => $xnovaId,
                    'finanzas_cuenta_id' => $cuentaDestino->id,
                    'nombre' => $subcuenta->nombre,
                    'icono' => $subcuenta->icono,
                    'es_general' => false,
                    'active' => true,
                ]);
            }
        } else {
            $destino = FinanzasSubcuenta::query()
                ->with('cuenta:id,xnova_id,finanzas_propietario_id')
                ->where('xnova_id', $xnovaId)
                ->findOrFail((int) $data['finanzas_subcuenta_destino_id']);
        }

        if ((int) $destino->id === (int) $subcuenta->id) {
            return response()->json(['message' => 'El destino debe ser distinto al origen.'], 422);
        }

        $pendiente = (bool) ($data['pendiente'] ?? false);
        $descripcion = $data['descripcion'] ?? ('Transferencia a '.$destino->nombre);

        FinanzasMovimiento::query()->create([
            'xnova_id' => $xnovaId,
            'finanzas_cuenta_id' => $cuenta->id,
            'finanzas_cuenta_destino_id' => $destino->finanzas_cuenta_id,
            'finanzas_subcuenta_id' => $subcuenta->id,
            'finanzas_subcuenta_destino_id' => $destino->id,
            'finanzas_propietario_id' => $cuenta->finanzas_propietario_id,
            'tipo' => FinanzasMovimiento::TIPO_TRANSFERENCIA,
            'descripcion' => $descripcion,
            'categoria' => 'Transferencia',
            'monto' => $data['monto'],
            'fecha' => now()->toDateString(),
            'pendiente' => $pendiente,
        ]);

        return response()->json([
            'message' => $pendiente ? 'Transferencia dejada como pendiente.' : 'Transferencia realizada.',
            'pendiente' => $pendiente,
        ]);
    }

    public function anularMovimientoSubcuenta(Request $request, FinanzasCuenta $cuenta, FinanzasSubcuenta $subcuenta, FinanzasMovimiento $movimiento): JsonResponse
    {
        $this->ensureBolsilloBelongsToCuenta($cuenta, $subcuenta);

        if ((int) $movimiento->xnova_id !== (int) $cuenta->xnova_id) {
            abort(404);
        }

        $perteneceASubcuenta = (int) $movimiento->finanzas_subcuenta_id === (int) $subcuenta->id
            || (int) $movimiento->finanzas_subcuenta_destino_id === (int) $subcuenta->id;

        if (! $perteneceASubcuenta) {
            abort(404);
        }

        if ($movimiento->anulado_en !== null) {
            return response()->json(['message' => 'Este movimiento ya está anulado.'], 422);
        }

        $data = $request->validate([
            'motivo' => ['required', 'string', 'min:3', 'max:240'],
        ], [
            'motivo.required' => 'Indica un motivo para anular el movimiento.',
            'motivo.min' => 'El motivo debe tener al menos 3 caracteres.',
        ]);

        $montoOriginal = (float) $movimiento->monto;

        DB::transaction(function () use ($movimiento, $data, $montoOriginal): void {
            // Revertir el abono a una deuda: el saldo pendiente vuelve a subir.
            if ($movimiento->finanzas_deuda_id) {
                $deuda = FinanzasDeuda::query()
                    ->where('xnova_id', $movimiento->xnova_id)
                    ->find($movimiento->finanzas_deuda_id);

                if ($deuda) {
                    $deuda->update([
                        'saldo_pendiente' => min((float) $deuda->monto_original, (float) $deuda->saldo_pendiente + $montoOriginal),
                    ]);
                }
            }

            // Revertir el cobro de una cuenta por cobrar: el saldo pendiente vuelve a subir.
            if ($movimiento->finanzas_cuenta_por_cobrar_id) {
                $cobrar = FinanzasCuentaPorCobrar::query()
                    ->where('xnova_id', $movimiento->xnova_id)
                    ->find($movimiento->finanzas_cuenta_por_cobrar_id);

                if ($cobrar) {
                    $cobrar->update([
                        'saldo_pendiente' => min((float) $cobrar->monto_original, (float) $cobrar->saldo_pendiente + $montoOriginal),
                    ]);
                }
            }

            $movimiento->update([
                'monto_original' => $montoOriginal,
                'monto' => 0,
                'anulado_en' => now(),
                'motivo_anulacion' => $data['motivo'],
            ]);
        });

        return response()->json(['message' => 'Movimiento anulado.']);
    }

    /**
     * Pantalla de operaciones pendientes de confirmación (transferencias, pagos
     * de deuda y cobros marcados como pendientes). Tarjetas para confirmar rápido.
     */
    public function pendientes(): View
    {
        $xnovaId = $this->currentXnovaId();

        $movimientos = FinanzasMovimiento::query()
            ->with([
                'cuenta:id,nombre,moneda',
                'cuentaDestino:id,nombre',
                'subcuenta:id,nombre',
                'subcuentaDestino:id,nombre',
                'deuda:id,nombre',
                'cuentaPorCobrar:id,nombre',
            ])
            ->where('xnova_id', $xnovaId)
            ->where('pendiente', true)
            ->whereNull('anulado_en')
            ->orderBy('fecha')
            ->orderBy('id')
            ->get();

        $items = $movimientos->map(function (FinanzasMovimiento $m): array {
            // Tipo legible y a dónde va el dinero.
            if ($m->finanzas_deuda_id) {
                $clase = 'deuda';
                $titulo = 'Pago de deuda';
                $destino = $m->deuda?->nombre;
            } elseif ($m->finanzas_cuenta_por_cobrar_id) {
                $clase = 'cobro';
                $titulo = 'Cobro';
                $destino = $m->cuentaPorCobrar?->nombre;
            } elseif ($m->tipo === FinanzasMovimiento::TIPO_TRANSFERENCIA) {
                $clase = 'transferencia';
                $titulo = 'Transferencia';
                $destino = trim(($m->cuentaDestino?->nombre ? $m->cuentaDestino->nombre . ' · ' : '') . ($m->subcuentaDestino?->nombre ?? ''));
            } else {
                $clase = $m->tipo;
                $titulo = ucfirst((string) $m->tipo);
                $destino = null;
            }

            return [
                'id' => $m->id,
                'clase' => $clase,
                'titulo' => $titulo,
                'descripcion' => $m->descripcion,
                'monto' => (float) $m->monto,
                'moneda' => $m->cuenta?->moneda ?? 'LPS',
                'origen' => trim(($m->cuenta?->nombre ?? '') . ($m->subcuenta?->nombre ? ' · ' . $m->subcuenta->nombre : '')),
                'destino' => $destino ?: '—',
                'fecha' => $m->fecha?->format('d/m/Y'),
            ];
        })->values();

        return view('finanzas.pendientes.index', [
            'pendientes' => $items,
            'confirmarUrlTemplate' => route('finanzas.pendientes.confirmar', 'MOV_ID'),
            'anularUrlTemplate' => route('finanzas.pendientes.anular', 'MOV_ID'),
        ]);
    }

    /**
     * Confirma una operación pendiente: ahora sí mueve el saldo y aplica los
     * efectos sobre la deuda o cuenta por cobrar asociada.
     */
    public function confirmarPendiente(FinanzasMovimiento $movimiento): JsonResponse
    {
        $this->ensureCurrentXnova($movimiento);

        if (! $movimiento->pendiente || $movimiento->anulado_en !== null) {
            return response()->json(['message' => 'Esta operación ya no está pendiente.'], 422);
        }

        $monto = (float) $movimiento->monto;

        DB::transaction(function () use ($movimiento, $monto): void {
            if ($movimiento->finanzas_deuda_id) {
                $deuda = FinanzasDeuda::query()
                    ->where('xnova_id', $movimiento->xnova_id)
                    ->find($movimiento->finanzas_deuda_id);
                if ($deuda) {
                    $deuda->update(['saldo_pendiente' => max(0, (float) $deuda->saldo_pendiente - $monto)]);
                }
            }

            if ($movimiento->finanzas_cuenta_por_cobrar_id) {
                $cobrar = FinanzasCuentaPorCobrar::query()
                    ->where('xnova_id', $movimiento->xnova_id)
                    ->find($movimiento->finanzas_cuenta_por_cobrar_id);
                if ($cobrar) {
                    $cobrar->update(['saldo_pendiente' => max(0, (float) $cobrar->saldo_pendiente - $monto)]);
                }
            }

            $movimiento->update([
                'pendiente' => false,
                'confirmado_en' => now(),
            ]);
        });

        return response()->json(['message' => 'Operación confirmada.']);
    }

    /**
     * Cancela una operación pendiente. Como no afectó saldos, basta con anularla.
     */
    public function anularPendiente(Request $request, FinanzasMovimiento $movimiento): JsonResponse
    {
        $this->ensureCurrentXnova($movimiento);

        if (! $movimiento->pendiente || $movimiento->anulado_en !== null) {
            return response()->json(['message' => 'Esta operación ya no está pendiente.'], 422);
        }

        $movimiento->update([
            'monto_original' => $movimiento->monto,
            'monto' => 0,
            'pendiente' => false,
            'anulado_en' => now(),
            'motivo_anulacion' => trim((string) $request->input('motivo')) ?: 'Pendiente cancelado',
        ]);

        return response()->json(['message' => 'Pendiente cancelado.']);
    }

    public function ingresosFijos(): View
    {
        $xnovaId = $this->currentXnovaId();

        return view('finanzas.ingresos-fijos.index', [
            'propietarios' => $this->propietariosActivos($xnovaId),
        ]);
    }

    public function ingresosFijosData(Request $request): JsonResponse
    {
        $validated = $this->validateListRequest($request);
        $search = trim((string) ($validated['search'] ?? ''));
        $perPage = (int) ($validated['per_page'] ?? 10);
        $propietarioId = $request->filled('propietario_id') ? (int) $request->input('propietario_id') : null;

        $ingresos = $this->ingresosFijosBaseQuery($search, $propietarioId)
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        $totalMonto = (float) $this->ingresosFijosBaseQuery($search, $propietarioId)->sum('monto');
        $moneda = $this->monedaDefault();

        return response()->json([
            'rows' => view('finanzas.ingresos-fijos.partials.rows', ['ingresos' => $ingresos])->render(),
            'cards' => view('finanzas.ingresos-fijos.partials.cards', ['ingresos' => $ingresos])->render(),
            'pagination' => view('finanzas.ingresos-fijos.partials.pagination', ['ingresos' => $ingresos])->render(),
            'meta' => $this->paginationMeta($ingresos),
            'total_monto' => $totalMonto,
            'total_label' => $moneda . ' ' . number_format($totalMonto, 2),
        ]);
    }

    public function createIngresoFijo(): View
    {
        $xnovaId = $this->currentXnovaId();

        return view('finanzas.ingresos-fijos.form', [
            'ingreso' => new FinanzasIngresoFijo(['active' => true, 'frecuencia' => 'mensual']),
            'isEditing' => false,
            'propietarios' => $this->propietariosActivos($xnovaId),
            'cuentas' => $this->cuentasActivas($xnovaId),
        ]);
    }

    public function storeIngresoFijo(Request $request): RedirectResponse
    {
        $data = $this->validateFijo($request);
        $data['saldo_restante'] = ! empty($data['preguntar_al_usar']) ? (float) $data['monto'] : null;

        FinanzasIngresoFijo::query()->create($data);

        return redirect()->route('finanzas.ingresos-fijos.create')->with('status', 'Ingreso fijo guardado correctamente.');
    }

    public function showIngresoFijo(FinanzasIngresoFijo $ingreso): View
    {
        $this->ensureCurrentXnova($ingreso);
        $ingreso->load(['propietario:id,nombre', 'cuenta:id,nombre']);

        return view('finanzas.ingresos-fijos.show', [
            'ingreso' => $ingreso,
        ]);
    }

    public function editIngresoFijo(FinanzasIngresoFijo $ingreso): View
    {
        $this->ensureCurrentXnova($ingreso);
        $xnovaId = $this->currentXnovaId();

        return view('finanzas.ingresos-fijos.form', [
            'ingreso' => $ingreso,
            'isEditing' => true,
            'propietarios' => $this->propietariosActivos($xnovaId),
            'cuentas' => $this->cuentasActivas($xnovaId),
        ]);
    }

    public function updateIngresoFijo(Request $request, FinanzasIngresoFijo $ingreso): RedirectResponse
    {
        $this->ensureCurrentXnova($ingreso);

        $data = $this->validateFijo($request);
        $preguntar = ! empty($data['preguntar_al_usar']);
        $erapreguntar = (bool) $ingreso->preguntar_al_usar;

        if ($preguntar && ! $erapreguntar) {
            $data['saldo_restante'] = (float) $data['monto'];
        } elseif (! $preguntar) {
            $data['saldo_restante'] = null;
        }

        $ingreso->update($data);

        return redirect()->route('finanzas.ingresos-fijos.show', $ingreso)->with('status', 'Ingreso fijo actualizado correctamente.');
    }

    public function gastosFijos(): View
    {
        $xnovaId = $this->currentXnovaId();

        return view('finanzas.gastos-fijos.index', [
            'propietarios' => $this->propietariosActivos($xnovaId),
        ]);
    }

    public function gastosFijosData(Request $request): JsonResponse
    {
        $validated = $this->validateListRequest($request);
        $search = trim((string) ($validated['search'] ?? ''));
        $perPage = (int) ($validated['per_page'] ?? 10);
        $propietarioId = $request->filled('propietario_id') ? (int) $request->input('propietario_id') : null;

        $gastos = $this->gastosFijosBaseQuery($search, $propietarioId)
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        $totalMonto = (float) $this->gastosFijosBaseQuery($search, $propietarioId)->sum('monto');
        $moneda = $this->monedaDefault();

        return response()->json([
            'rows' => view('finanzas.gastos-fijos.partials.rows', ['gastos' => $gastos])->render(),
            'cards' => view('finanzas.gastos-fijos.partials.cards', ['gastos' => $gastos])->render(),
            'pagination' => view('finanzas.gastos-fijos.partials.pagination', ['gastos' => $gastos])->render(),
            'meta' => $this->paginationMeta($gastos),
            'total_monto' => $totalMonto,
            'total_label' => $moneda . ' ' . number_format($totalMonto, 2),
        ]);
    }

    /**
     * Devuelve la moneda predeterminada configurada en la empresa actual.
     */
    protected function monedaDefault(): string
    {
        $value = \App\Models\Xnova::query()
            ->where('id', $this->currentXnovaId())
            ->value('moneda_default');

        return $value ?: 'LPS';
    }

    public function createGastoFijo(): View
    {
        $xnovaId = $this->currentXnovaId();

        return view('finanzas.gastos-fijos.form', [
            'gasto' => new FinanzasGastoFijo(['active' => true, 'frecuencia' => 'mensual']),
            'isEditing' => false,
            'propietarios' => $this->propietariosActivos($xnovaId),
        ]);
    }

    public function storeGastoFijo(Request $request): RedirectResponse
    {
        $data = $this->validateGastoFijo($request);
        $data['saldo_restante'] = ! empty($data['preguntar_al_usar']) ? (float) $data['monto'] : null;

        FinanzasGastoFijo::query()->create($data);

        return redirect()->route('finanzas.gastos-fijos.create')->with('status', 'Gasto fijo guardado correctamente.');
    }

    public function showGastoFijo(FinanzasGastoFijo $gasto): View
    {
        $this->ensureCurrentXnova($gasto);
        $gasto->load(['propietario:id,nombre']);

        return view('finanzas.gastos-fijos.show', [
            'gasto' => $gasto,
        ]);
    }

    public function editGastoFijo(FinanzasGastoFijo $gasto): View
    {
        $this->ensureCurrentXnova($gasto);
        $xnovaId = $this->currentXnovaId();

        return view('finanzas.gastos-fijos.form', [
            'gasto' => $gasto,
            'isEditing' => true,
            'propietarios' => $this->propietariosActivos($xnovaId),
        ]);
    }

    public function updateGastoFijo(Request $request, FinanzasGastoFijo $gasto): RedirectResponse
    {
        $this->ensureCurrentXnova($gasto);

        $data = $this->validateGastoFijo($request);
        $preguntar = ! empty($data['preguntar_al_usar']);
        $erapreguntar = (bool) $gasto->preguntar_al_usar;

        if ($preguntar && ! $erapreguntar) {
            $data['saldo_restante'] = (float) $data['monto'];
        } elseif (! $preguntar) {
            $data['saldo_restante'] = null;
        }

        $gasto->update($data);

        return redirect()->route('finanzas.gastos-fijos.show', $gasto)->with('status', 'Gasto fijo actualizado correctamente.');
    }

    public function deudas(): View
    {
        $xnovaId = $this->currentXnovaId();
        $cuentasDestino = $this->bolsillosDisponiblesParaSeleccion($xnovaId);

        return view('finanzas.deudas.index', [
            'moneda' => $this->monedaDefault(),
            'cuentasDestino' => $cuentasDestino,
            'propietarios' => $this->propietariosDeCuentas($cuentasDestino),
            'puedePagar' => app(PermissionService::class)->userHasPermission(auth()->user(), 'finanzas.deudas.pagar'),
        ]);
    }

    public function storePagoDeuda(Request $request, FinanzasDeuda $deuda): JsonResponse
    {
        $this->ensureCurrentXnova($deuda);
        $xnovaId = $deuda->xnova_id;

        $data = $request->validate([
            'finanzas_subcuenta_id' => ['required', 'integer', Rule::exists('finanzas_subcuentas', 'id')->where('xnova_id', $xnovaId)],
            'monto' => ['required', 'numeric', 'min:0.01'],
            'pendiente' => ['nullable', 'boolean'],
        ]);

        $subcuenta = FinanzasSubcuenta::query()->where('xnova_id', $xnovaId)->findOrFail($data['finanzas_subcuenta_id']);
        $cuenta = FinanzasCuenta::query()->where('xnova_id', $xnovaId)->findOrFail($subcuenta->finanzas_cuenta_id);

        $pendiente = (bool) ($data['pendiente'] ?? false);
        $monto = round((float) $data['monto'], 2);
        $saldoPendiente = (float) $deuda->saldo_pendiente;
        $saldoBolsillo = (float) ($this->balancesPorSubcuenta($xnovaId, [$subcuenta->id])[$subcuenta->id] ?? 0);

        if ($monto > $saldoPendiente) {
            return response()->json([
                'message' => 'El abono supera el saldo pendiente de la deuda.',
                'max' => $saldoPendiente,
            ], 422);
        }

        // Se permite abono parcial, pero nunca por encima del saldo disponible del bolsillo.
        if ($monto > $saldoBolsillo) {
            return response()->json([
                'message' => 'El bolsillo no tiene saldo suficiente. Máximo disponible: ' . $cuenta->moneda . ' ' . number_format(max(0, $saldoBolsillo), 2) . '.',
                'max' => max(0, $saldoBolsillo),
            ], 422);
        }

        DB::transaction(function () use ($cuenta, $subcuenta, $deuda, $monto, $xnovaId, $pendiente): void {
            FinanzasMovimiento::query()->create([
                'xnova_id' => $xnovaId,
                'finanzas_cuenta_id' => $cuenta->id,
                'finanzas_subcuenta_id' => $subcuenta->id,
                'finanzas_propietario_id' => $cuenta->finanzas_propietario_id,
                'finanzas_deuda_id' => $deuda->id,
                'tipo' => FinanzasMovimiento::TIPO_GASTO,
                'descripcion' => 'Pago deuda: ' . $deuda->nombre,
                'categoria' => 'Deudas',
                'monto' => $monto,
                'fecha' => now()->toDateString(),
                'pendiente' => $pendiente,
            ]);

            if (! $pendiente) {
                $deuda->decrement('saldo_pendiente', $monto);
            }
        });

        if ($pendiente) {
            return response()->json([
                'message' => 'Pago dejado como pendiente.',
                'pendiente' => true,
                'saldo_pendiente' => $saldoPendiente,
            ]);
        }

        $deuda->refresh();
        $saldoFinal = (float) $deuda->saldo_pendiente;

        return response()->json([
            'message' => $saldoFinal <= 0 ? 'Deuda pagada por completo.' : 'Abono registrado.',
            'saldo_pendiente' => $saldoFinal,
            'pagada' => $saldoFinal <= 0,
        ]);
    }

    public function deudasData(Request $request): JsonResponse
    {
        $validated = $this->validateListRequest($request);
        $search = trim((string) ($validated['search'] ?? ''));
        $perPage = (int) ($validated['per_page'] ?? 10);
        $estado = in_array($request->input('estado'), ['pendiente', 'pagada'], true) ? $request->input('estado') : null;

        $deudas = $this->deudasBaseQuery($search, $estado)
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        $totalPendiente = (float) $this->deudasBaseQuery($search, 'pendiente')->sum('saldo_pendiente');
        $moneda = $this->monedaDefault();

        $puedePagar = app(PermissionService::class)->userHasPermission(auth()->user(), 'finanzas.deudas.pagar');

        return response()->json([
            'cards' => view('finanzas.deudas.partials.cards', ['deudas' => $deudas, 'moneda' => $moneda, 'puedePagar' => $puedePagar])->render(),
            'pagination' => view('finanzas.deudas.partials.pagination', ['deudas' => $deudas])->render(),
            'meta' => $this->paginationMeta($deudas),
            'total_pendiente' => $totalPendiente,
            'total_label' => $moneda . ' ' . number_format($totalPendiente, 2),
        ]);
    }

    public function createDeuda(): View
    {
        return view('finanzas.deudas.form', [
            'deuda' => new FinanzasDeuda(['active' => true, 'fecha' => now()->toDateString()]),
            'isEditing' => false,
            'moneda' => $this->monedaDefault(),
        ]);
    }

    public function storeDeuda(Request $request): RedirectResponse
    {
        $data = $this->validateDeuda($request);
        $data['saldo_pendiente'] = (float) $data['monto_original'];

        FinanzasDeuda::query()->create($data);

        return redirect()->route('finanzas.deudas.create')->with('status', 'Deuda guardada correctamente.');
    }

    public function showDeuda(FinanzasDeuda $deuda): View
    {
        $this->ensureCurrentXnova($deuda);

        $deuda->load(['pagos' => fn ($query) => $query
            ->with(['cuenta:id,nombre', 'subcuenta:id,nombre'])
            ->orderByDesc('fecha')
            ->orderByDesc('id')]);

        return view('finanzas.deudas.show', [
            'deuda' => $deuda,
            'moneda' => $this->monedaDefault(),
        ]);
    }

    public function editDeuda(FinanzasDeuda $deuda): View
    {
        $this->ensureCurrentXnova($deuda);

        return view('finanzas.deudas.form', [
            'deuda' => $deuda,
            'isEditing' => true,
            'moneda' => $this->monedaDefault(),
        ]);
    }

    public function updateDeuda(Request $request, FinanzasDeuda $deuda): RedirectResponse
    {
        $this->ensureCurrentXnova($deuda);

        $data = $this->validateDeuda($request);

        // Conservar lo ya pagado: el saldo pendiente se reajusta segun el nuevo monto total.
        $pagado = max(0, (float) $deuda->monto_original - (float) $deuda->saldo_pendiente);
        $data['saldo_pendiente'] = max(0, (float) $data['monto_original'] - $pagado);

        $deuda->update($data);

        return redirect()->route('finanzas.deudas.show', $deuda)->with('status', 'Deuda actualizada correctamente.');
    }

    public function cuentasPorCobrar(): View
    {
        $xnovaId = $this->currentXnovaId();
        $cuentasDestino = $this->bolsillosDisponiblesParaSeleccion($xnovaId);

        return view('finanzas.cuentas-por-cobrar.index', [
            'moneda' => $this->monedaDefault(),
            'cuentasDestino' => $cuentasDestino,
            'propietarios' => $this->propietariosDeCuentas($cuentasDestino),
            'puedeCobrar' => app(PermissionService::class)->userHasPermission(auth()->user(), 'finanzas.cuentas-por-cobrar.cobrar'),
        ]);
    }

    public function cuentasPorCobrarData(Request $request): JsonResponse
    {
        $validated = $this->validateListRequest($request);
        $search = trim((string) ($validated['search'] ?? ''));
        $perPage = (int) ($validated['per_page'] ?? 10);
        $estado = in_array($request->input('estado'), ['pendiente', 'cobrada'], true) ? $request->input('estado') : null;

        $items = $this->cuentasPorCobrarBaseQuery($search, $estado)
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        $totalPendiente = (float) $this->cuentasPorCobrarBaseQuery($search, 'pendiente')->sum('saldo_pendiente');
        $moneda = $this->monedaDefault();
        $puedeCobrar = app(PermissionService::class)->userHasPermission(auth()->user(), 'finanzas.cuentas-por-cobrar.cobrar');

        return response()->json([
            'cards' => view('finanzas.cuentas-por-cobrar.partials.cards', ['items' => $items, 'moneda' => $moneda, 'puedeCobrar' => $puedeCobrar])->render(),
            'pagination' => view('finanzas.cuentas-por-cobrar.partials.pagination', ['items' => $items])->render(),
            'meta' => $this->paginationMeta($items),
            'total_pendiente' => $totalPendiente,
            'total_label' => $moneda . ' ' . number_format($totalPendiente, 2),
        ]);
    }

    public function createCuentaPorCobrar(): View
    {
        return view('finanzas.cuentas-por-cobrar.form', [
            'cuentaPorCobrar' => new FinanzasCuentaPorCobrar(['active' => true, 'fecha' => now()->toDateString()]),
            'isEditing' => false,
            'moneda' => $this->monedaDefault(),
        ]);
    }

    public function storeCuentaPorCobrar(Request $request): RedirectResponse
    {
        $data = $this->validateCuentaPorCobrar($request);
        $data['saldo_pendiente'] = (float) $data['monto_original'];

        FinanzasCuentaPorCobrar::query()->create($data);

        return redirect()->route('finanzas.cuentas-por-cobrar.create')->with('status', 'Cuenta por cobrar guardada correctamente.');
    }

    public function showCuentaPorCobrar(FinanzasCuentaPorCobrar $cuentaPorCobrar): View
    {
        $this->ensureCurrentXnova($cuentaPorCobrar);

        $cuentaPorCobrar->load(['cobros' => fn ($query) => $query
            ->with(['cuenta:id,nombre', 'subcuenta:id,nombre'])
            ->orderByDesc('fecha')
            ->orderByDesc('id')]);

        return view('finanzas.cuentas-por-cobrar.show', [
            'item' => $cuentaPorCobrar,
            'moneda' => $this->monedaDefault(),
        ]);
    }

    public function editCuentaPorCobrar(FinanzasCuentaPorCobrar $cuentaPorCobrar): View
    {
        $this->ensureCurrentXnova($cuentaPorCobrar);

        return view('finanzas.cuentas-por-cobrar.form', [
            'cuentaPorCobrar' => $cuentaPorCobrar,
            'isEditing' => true,
            'moneda' => $this->monedaDefault(),
        ]);
    }

    public function updateCuentaPorCobrar(Request $request, FinanzasCuentaPorCobrar $cuentaPorCobrar): RedirectResponse
    {
        $this->ensureCurrentXnova($cuentaPorCobrar);

        $data = $this->validateCuentaPorCobrar($request);

        // Conservar lo ya cobrado: el saldo pendiente se reajusta segun el nuevo monto total.
        $cobrado = max(0, (float) $cuentaPorCobrar->monto_original - (float) $cuentaPorCobrar->saldo_pendiente);
        $data['saldo_pendiente'] = max(0, (float) $data['monto_original'] - $cobrado);

        $cuentaPorCobrar->update($data);

        return redirect()->route('finanzas.cuentas-por-cobrar.show', $cuentaPorCobrar)->with('status', 'Cuenta por cobrar actualizada correctamente.');
    }

    public function storeCobro(Request $request, FinanzasCuentaPorCobrar $cuentaPorCobrar): JsonResponse
    {
        $this->ensureCurrentXnova($cuentaPorCobrar);
        $xnovaId = $cuentaPorCobrar->xnova_id;

        $data = $request->validate([
            'finanzas_subcuenta_id' => ['required', 'integer', Rule::exists('finanzas_subcuentas', 'id')->where('xnova_id', $xnovaId)],
            'monto' => ['required', 'numeric', 'min:0.01'],
            'pendiente' => ['nullable', 'boolean'],
        ]);

        $subcuenta = FinanzasSubcuenta::query()->where('xnova_id', $xnovaId)->findOrFail($data['finanzas_subcuenta_id']);
        $cuenta = FinanzasCuenta::query()->where('xnova_id', $xnovaId)->findOrFail($subcuenta->finanzas_cuenta_id);

        $pendiente = (bool) ($data['pendiente'] ?? false);
        $monto = round((float) $data['monto'], 2);
        $saldoPendiente = (float) $cuentaPorCobrar->saldo_pendiente;

        if ($monto > $saldoPendiente) {
            return response()->json([
                'message' => 'El cobro supera el saldo pendiente.',
                'max' => $saldoPendiente,
            ], 422);
        }

        DB::transaction(function () use ($cuenta, $subcuenta, $cuentaPorCobrar, $monto, $xnovaId, $pendiente): void {
            FinanzasMovimiento::query()->create([
                'xnova_id' => $xnovaId,
                'finanzas_cuenta_id' => $cuenta->id,
                'finanzas_subcuenta_id' => $subcuenta->id,
                'finanzas_propietario_id' => $cuenta->finanzas_propietario_id,
                'finanzas_cuenta_por_cobrar_id' => $cuentaPorCobrar->id,
                'tipo' => FinanzasMovimiento::TIPO_INGRESO,
                'descripcion' => 'Cobro: ' . $cuentaPorCobrar->nombre,
                'categoria' => 'Cobros',
                'monto' => $monto,
                'fecha' => now()->toDateString(),
                'pendiente' => $pendiente,
            ]);

            if (! $pendiente) {
                $cuentaPorCobrar->decrement('saldo_pendiente', $monto);
            }
        });

        if ($pendiente) {
            return response()->json([
                'message' => 'Cobro dejado como pendiente.',
                'pendiente' => true,
                'saldo_pendiente' => $saldoPendiente,
            ]);
        }

        $cuentaPorCobrar->refresh();
        $saldoFinal = (float) $cuentaPorCobrar->saldo_pendiente;

        return response()->json([
            'message' => $saldoFinal <= 0 ? 'Cuenta cobrada por completo.' : 'Cobro registrado.',
            'saldo_pendiente' => $saldoFinal,
            'cobrada' => $saldoFinal <= 0,
        ]);
    }

    public function movimientos(): View
    {
        return view('finanzas.movimientos.index');
    }

    public function movimientosData(Request $request): JsonResponse
    {
        $validated = $this->validateListRequest($request);
        $search = trim((string) ($validated['search'] ?? ''));
        $perPage = (int) ($validated['per_page'] ?? 10);
        $movimientos = $this->movimientosBaseQuery($search)
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        return response()->json([
            'rows' => view('finanzas.movimientos.partials.rows', ['movimientos' => $movimientos])->render(),
            'cards' => view('finanzas.movimientos.partials.cards', ['movimientos' => $movimientos])->render(),
            'pagination' => view('finanzas.movimientos.partials.pagination', ['movimientos' => $movimientos])->render(),
            'meta' => $this->paginationMeta($movimientos),
        ]);
    }

    public function createMovimiento(): View
    {
        $xnovaId = $this->currentXnovaId();

        return view('finanzas.movimientos.form', [
            'movimiento' => new FinanzasMovimiento(['tipo' => FinanzasMovimiento::TIPO_GASTO, 'fecha' => now()]),
            'isEditing' => false,
            'propietarios' => $this->propietariosActivos($xnovaId),
            'cuentas' => $this->cuentasActivas($xnovaId),
            'subcuentas' => $this->subcuentasActivas($xnovaId),
        ]);
    }

    public function storeMovimiento(Request $request): RedirectResponse
    {
        $movimiento = FinanzasMovimiento::query()->create($this->validateMovimiento($request));

        return redirect()->route('finanzas.movimientos.create')->with('status', 'Movimiento guardado correctamente.');
    }

    public function showMovimiento(FinanzasMovimiento $movimiento): View
    {
        $this->ensureCurrentXnova($movimiento);
        $movimiento->load(['propietario:id,nombre', 'cuenta:id,nombre,moneda', 'cuentaDestino:id,nombre', 'subcuenta:id,nombre', 'subcuentaDestino:id,nombre']);

        return view('finanzas.movimientos.show', [
            'movimiento' => $movimiento,
        ]);
    }

    public function editMovimiento(FinanzasMovimiento $movimiento): View
    {
        $this->ensureCurrentXnova($movimiento);
        $xnovaId = $this->currentXnovaId();

        return view('finanzas.movimientos.form', [
            'movimiento' => $movimiento,
            'isEditing' => true,
            'propietarios' => $this->propietariosActivos($xnovaId),
            'cuentas' => $this->cuentasActivas($xnovaId),
            'subcuentas' => $this->subcuentasActivas($xnovaId),
        ]);
    }

    public function updateMovimiento(Request $request, FinanzasMovimiento $movimiento): RedirectResponse
    {
        $this->ensureCurrentXnova($movimiento);
        $movimiento->update($this->validateMovimiento($request));

        return redirect()->route('finanzas.movimientos.show', $movimiento)->with('status', 'Movimiento actualizado correctamente.');
    }

    /**
     * Opciones para la captura rápida (FAB global): cuentas activas (agrupables
     * por propietario), sus bolsillos y saldo, propietarios, categorías
     * sugeridas, favoritos (movimientos más repetidos) y la última cuenta usada.
     * Se consulta una sola vez al abrir el modal para no encarecer cada vista.
     */
    public function quickOpciones(): JsonResponse
    {
        $xnovaId = $this->currentXnovaId();

        // Cuentas con su propietario y bolsillos activos, más el saldo calculado.
        $cuentasModelos = FinanzasCuenta::query()
            ->where('xnova_id', $xnovaId)
            ->where('active', true)
            ->with([
                'propietario:id,nombre',
                'subcuentas' => function ($query): void {
                    $query->where('active', true)
                        ->orderByDesc('es_general')
                        ->orderBy('nombre');
                },
            ])
            ->orderBy('nombre')
            ->get();

        $saldos = $this->balancesPorCuenta($xnovaId, $cuentasModelos->pluck('id')->all());

        $subcuentaIds = $cuentasModelos->flatMap(fn ($cuenta) => $cuenta->subcuentas->pluck('id'))->all();
        $saldosBolsillos = $this->balancesPorSubcuenta($xnovaId, $subcuentaIds);

        $cuentas = $cuentasModelos
            ->map(fn ($cuenta) => [
                'id' => $cuenta->id,
                'nombre' => $cuenta->nombre,
                'moneda' => $cuenta->moneda,
                'saldo' => (float) ($saldos[$cuenta->id] ?? 0),
                'propietario_id' => $cuenta->finanzas_propietario_id,
                'propietario_nombre' => $cuenta->propietario?->nombre ?? 'Sin propietario',
                'bolsillos' => $cuenta->subcuentas->map(fn ($b) => [
                    'id' => $b->id,
                    'nombre' => $b->nombre,
                    'es_general' => (bool) $b->es_general,
                    'saldo' => (float) ($saldosBolsillos[$b->id] ?? 0),
                ])->values()->all(),
            ])
            ->values();

        // Propietarios presentes (derivados de las cuentas), para el paso 1.
        $propietarios = $cuentas
            ->map(fn ($c) => [
                'id' => $c['propietario_id'],
                'nombre' => $c['propietario_nombre'],
            ])
            ->unique(fn ($p) => (string) ($p['id'] ?? 'null'))
            ->sortBy('nombre')
            ->values();

        // Categorías ya usadas (en movimientos y gastos fijos), sin repetir.
        $categorias = FinanzasMovimiento::query()
            ->where('xnova_id', $xnovaId)
            ->whereNotNull('categoria')
            ->where('categoria', '!=', '')
            ->distinct()
            ->orderBy('categoria')
            ->pluck('categoria')
            ->merge(
                FinanzasGastoFijo::query()
                    ->where('xnova_id', $xnovaId)
                    ->whereNotNull('categoria')
                    ->where('categoria', '!=', '')
                    ->distinct()
                    ->pluck('categoria')
            )
            ->unique()
            ->sort()
            ->values();

        // Favoritos: los movimientos (gasto/ingreso) más repetidos en los
        // últimos 120 días, con un toque para registrarlos de nuevo.
        $favoritos = FinanzasMovimiento::query()
            ->where('xnova_id', $xnovaId)
            ->whereIn('tipo', [FinanzasMovimiento::TIPO_GASTO, FinanzasMovimiento::TIPO_INGRESO])
            ->whereNull('anulado_en')
            ->where('fecha', '>=', now()->subDays(120)->toDateString())
            ->selectRaw('descripcion, tipo, MAX(categoria) as categoria, MAX(finanzas_cuenta_id) as finanzas_cuenta_id, MAX(monto) as monto, COUNT(*) as veces')
            ->groupBy('descripcion', 'tipo')
            ->orderByDesc('veces')
            ->limit(6)
            ->get()
            ->map(fn ($fav) => [
                'descripcion' => $fav->descripcion,
                'tipo' => $fav->tipo,
                'categoria' => $fav->categoria,
                'finanzas_cuenta_id' => $fav->finanzas_cuenta_id,
                'monto' => (float) $fav->monto,
            ])
            ->values();

        // Último gasto/ingreso vigente: cuenta y bolsillo para preseleccionarlos
        // al abrir el modal (las capturas repetidas quedan en dos o tres toques).
        $ultimo = FinanzasMovimiento::query()
            ->where('xnova_id', $xnovaId)
            ->whereIn('tipo', [FinanzasMovimiento::TIPO_GASTO, FinanzasMovimiento::TIPO_INGRESO])
            ->whereNull('anulado_en')
            ->orderByDesc('id')
            ->first(['finanzas_cuenta_id', 'finanzas_subcuenta_id']);

        return response()->json([
            'cuentas' => $cuentas,
            'propietarios' => $propietarios,
            'categorias' => $categorias,
            'favoritos' => $favoritos,
            'ultima_cuenta_id' => $ultimo ? (int) $ultimo->finanzas_cuenta_id : ($cuentas[0]['id'] ?? null),
            'ultima_seleccion' => $ultimo ? [
                'cuenta_id' => (int) $ultimo->finanzas_cuenta_id,
                'subcuenta_id' => $ultimo->finanzas_subcuenta_id ? (int) $ultimo->finanzas_subcuenta_id : null,
            ] : null,
        ]);
    }

    /**
     * Captura rápida de un ingreso o gasto desde el FAB global. Crea el
     * movimiento en el bolsillo "General" de la cuenta. Devuelve JSON para que
     * el modal no recargue la página.
     */
    public function quickStore(Request $request): JsonResponse
    {
        $xnovaId = $this->currentXnovaId();

        $data = $request->validate([
            'tipo' => ['required', Rule::in([FinanzasMovimiento::TIPO_INGRESO, FinanzasMovimiento::TIPO_GASTO])],
            'finanzas_cuenta_id' => ['required', 'integer', Rule::exists('finanzas_cuentas', 'id')->where('xnova_id', $xnovaId)],
            'finanzas_subcuenta_id' => ['nullable', 'integer', Rule::exists('finanzas_subcuentas', 'id')->where('xnova_id', $xnovaId)],
            'monto' => ['required', 'numeric', 'min:0.01'],
            'descripcion' => ['nullable', 'string', 'max:180'],
            'categoria' => ['nullable', 'string', 'max:80'],
            'fecha' => ['nullable', 'date'],
        ]);

        $cuenta = FinanzasCuenta::query()
            ->where('xnova_id', $xnovaId)
            ->findOrFail((int) $data['finanzas_cuenta_id']);

        $descripcion = trim((string) ($data['descripcion'] ?? ''));
        if ($descripcion === '') {
            $descripcion = $data['tipo'] === FinanzasMovimiento::TIPO_INGRESO ? 'Ingreso rápido' : 'Gasto rápido';
        }

        $movimiento = FinanzasMovimiento::query()->create([
            'xnova_id' => $xnovaId,
            'finanzas_cuenta_id' => $cuenta->id,
            'finanzas_subcuenta_id' => $this->resolveSubcuentaIdForCuenta($cuenta, (int) ($data['finanzas_subcuenta_id'] ?? 0)),
            'finanzas_propietario_id' => $cuenta->finanzas_propietario_id,
            'tipo' => $data['tipo'],
            'descripcion' => $descripcion,
            'categoria' => $data['categoria'] ?? null,
            'monto' => round((float) $data['monto'], 2),
            'fecha' => $data['fecha'] ?? now()->toDateString(),
        ]);

        $saldo = (float) ($this->balancesPorCuenta($xnovaId, [$cuenta->id])[$cuenta->id] ?? 0);
        $subcuentaId = $movimiento->finanzas_subcuenta_id;
        $saldoBolsillo = (float) ($this->balancesPorSubcuenta($xnovaId, [$subcuentaId])[$subcuentaId] ?? 0);

        return response()->json([
            'message' => $data['tipo'] === FinanzasMovimiento::TIPO_INGRESO ? 'Ingreso registrado.' : 'Gasto registrado.',
            'movimiento_id' => $movimiento->id,
            'saldo_cuenta' => $saldo,
            'subcuenta_id' => $subcuentaId,
            'saldo_bolsillo' => $saldoBolsillo,
            'moneda' => $cuenta->moneda,
        ]);
    }

    /**
     * Catálogo de los reportes disponibles. Cada uno declara sus filtros.
     */
    protected function definicionesReportes(): array
    {
        return [
            'flujo-caja' => [
                'titulo' => 'Flujo de caja',
                'descripcion' => 'Ingresos contra gastos y flujo neto, mes a mes dentro del periodo.',
                'icono' => 'chart-bar',
                'filtros' => ['rango', 'cuenta', 'propietario'],
            ],
            'gastos-categoria' => [
                'titulo' => 'Gastos por categoría',
                'descripcion' => 'Desglose de en qué se va el dinero, para detectar dónde recortar.',
                'icono' => 'banknotes',
                'filtros' => ['rango', 'cuenta'],
            ],
            'libro-movimientos' => [
                'titulo' => 'Libro de movimientos',
                'descripcion' => 'Detalle completo de cada movimiento, ideal para contabilidad y auditoría.',
                'icono' => 'book-open',
                'filtros' => ['rango', 'cuenta', 'tipo', 'propietario'],
            ],
        ];
    }

    public function reportes(): View
    {
        $xnovaId = $this->currentXnovaId();

        return view('finanzas.reportes', [
            'reportes' => $this->definicionesReportes(),
            'cuentas' => $this->cuentasActivas($xnovaId),
            'propietarios' => $this->propietariosActivos($xnovaId),
            'tiposMovimiento' => [
                FinanzasMovimiento::TIPO_INGRESO => 'Ingreso',
                FinanzasMovimiento::TIPO_GASTO => 'Gasto',
                FinanzasMovimiento::TIPO_TRANSFERENCIA => 'Transferencia',
                FinanzasMovimiento::TIPO_AJUSTE => 'Ajuste',
            ],
        ]);
    }

    public function verReporte(string $tipo, Request $request): View
    {
        $reporte = $this->construirReporte($tipo, $request);

        return view('finanzas.reportes.ver', [
            'tipo' => $tipo,
            'reporte' => $reporte,
            'filtros' => $request->only(['fecha_inicio', 'fecha_fin', 'cuenta', 'propietario', 'tipo']),
            'imprimir' => $request->boolean('print'),
        ]);
    }

    public function exportReporteExcel(string $tipo, Request $request): StreamedResponse
    {
        $reporte = $this->construirReporte($tipo, $request);
        $nombre = $tipo . '-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($reporte): void {
            $out = fopen('php://output', 'w');
            // BOM UTF-8 para que Excel muestre bien los acentos
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, [$reporte['titulo']]);
            fputcsv($out, [$reporte['subtitulo']]);
            fputcsv($out, []);
            fputcsv($out, $reporte['columnas']);

            foreach ($reporte['filas'] as $fila) {
                fputcsv($out, $fila);
            }

            if (! empty($reporte['totales'])) {
                fputcsv($out, []);
                fputcsv($out, $reporte['totales']);
            }

            fclose($out);
        }, $nombre, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function exportReportePdf(string $tipo, Request $request)
    {
        $reporte = $this->construirReporte($tipo, $request);
        $pdfClass = '\\Barryvdh\\DomPDF\\Facade\\Pdf';

        // Si dompdf está disponible, generamos PDF real; si no, abrimos la vista imprimible.
        if (class_exists($pdfClass)) {
            $pdf = $pdfClass::loadView('finanzas.reportes.pdf', ['reporte' => $reporte])
                ->setPaper('a4', $tipo === 'libro-movimientos' ? 'landscape' : 'portrait');

            return $pdf->download($tipo . '-' . now()->format('Y-m-d') . '.pdf');
        }

        return redirect()->route('finanzas.reportes.ver', array_merge(
            ['tipo' => $tipo, 'print' => 1],
            $request->only(['fecha_inicio', 'fecha_fin', 'cuenta', 'propietario', 'tipo'])
        ));
    }

    /**
     * Aplica los filtros comunes (fechas, cuenta, propietario, tipo) a una consulta de movimientos.
     */
    protected function aplicarFiltrosMovimientos(Builder $query, Request $request, int $xnovaId): Builder
    {
        $query->where('xnova_id', $xnovaId)->whereNull('anulado_en');

        if ($request->filled('fecha_inicio')) {
            $query->whereDate('fecha', '>=', $request->date('fecha_inicio'));
        }

        if ($request->filled('fecha_fin')) {
            $query->whereDate('fecha', '<=', $request->date('fecha_fin'));
        }

        if ($request->filled('cuenta')) {
            $cuentaId = (int) $request->input('cuenta');
            $query->where(function (Builder $q) use ($cuentaId): void {
                $q->where('finanzas_cuenta_id', $cuentaId)
                    ->orWhere('finanzas_cuenta_destino_id', $cuentaId);
            });
        }

        if ($request->filled('propietario')) {
            $query->where('finanzas_propietario_id', (int) $request->input('propietario'));
        }

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->input('tipo'));
        }

        return $query;
    }

    protected function rangoLabel(Request $request): string
    {
        $ini = $request->filled('fecha_inicio') ? \Carbon\Carbon::parse($request->input('fecha_inicio'))->format('d/m/Y') : 'inicio';
        $fin = $request->filled('fecha_fin') ? \Carbon\Carbon::parse($request->input('fecha_fin'))->format('d/m/Y') : 'hoy';

        return "Periodo: {$ini} — {$fin}";
    }

    /**
     * Construye el contenido del reporte solicitado.
     *
     * @return array{titulo:string, subtitulo:string, columnas:array, filas:array, totales:array, moneda:string}
     */
    protected function construirReporte(string $tipo, Request $request): array
    {
        $definiciones = $this->definicionesReportes();
        abort_unless(isset($definiciones[$tipo]), 404);

        $xnovaId = $this->currentXnovaId();
        $moneda = optional($this->cuentasActivas($xnovaId)->first())->moneda ?? 'LPS';
        $titulo = $definiciones[$tipo]['titulo'];
        $subtitulo = $this->rangoLabel($request);

        $movimientos = $this->aplicarFiltrosMovimientos(
            FinanzasMovimiento::query()->with(['cuenta:id,nombre', 'cuentaDestino:id,nombre', 'subcuenta:id,nombre', 'propietario:id,nombre']),
            $request,
            $xnovaId
        )->orderBy('fecha')->orderBy('id')->get();

        if ($tipo === 'flujo-caja') {
            $porMes = $movimientos->groupBy(fn ($m) => \Carbon\Carbon::parse($m->fecha)->format('Y-m'));
            $filas = [];
            $totIng = 0.0;
            $totGas = 0.0;

            foreach ($porMes as $clave => $grupo) {
                $ing = (float) ($grupo->whereIn('tipo', [FinanzasMovimiento::TIPO_INGRESO, FinanzasMovimiento::TIPO_AJUSTE])->sum('monto'));
                $gas = (float) ($grupo->where('tipo', FinanzasMovimiento::TIPO_GASTO)->sum('monto'));
                $totIng += $ing;
                $totGas += $gas;
                $filas[] = [
                    \Carbon\Carbon::parse($clave . '-01')->locale('es')->isoFormat('MMMM YYYY'),
                    number_format($ing, 2),
                    number_format($gas, 2),
                    number_format($ing - $gas, 2),
                ];
            }

            return [
                'titulo' => $titulo,
                'subtitulo' => $subtitulo,
                'columnas' => ['Mes', "Ingresos ({$moneda})", "Gastos ({$moneda})", "Flujo neto ({$moneda})"],
                'filas' => $filas,
                'totales' => ['TOTAL', number_format($totIng, 2), number_format($totGas, 2), number_format($totIng - $totGas, 2)],
                'moneda' => $moneda,
            ];
        }

        if ($tipo === 'gastos-categoria') {
            $gastos = $movimientos->where('tipo', FinanzasMovimiento::TIPO_GASTO);
            $total = (float) $gastos->sum('monto');
            $porCat = $gastos
                ->groupBy(fn ($m) => filled($m->categoria) ? $m->categoria : 'Sin categoría')
                ->map(fn ($g) => (float) $g->sum('monto'))
                ->sortDesc();

            $filas = [];
            foreach ($porCat as $categoria => $monto) {
                $filas[] = [
                    $categoria,
                    number_format($monto, 2),
                    ($total > 0 ? number_format(($monto / $total) * 100, 1) : '0.0') . '%',
                ];
            }

            return [
                'titulo' => $titulo,
                'subtitulo' => $subtitulo,
                'columnas' => ['Categoría', "Total ({$moneda})", '% del total'],
                'filas' => $filas,
                'totales' => ['TOTAL', number_format($total, 2), '100%'],
                'moneda' => $moneda,
            ];
        }

        // libro-movimientos
        $tipoLabels = [
            FinanzasMovimiento::TIPO_INGRESO => 'Ingreso',
            FinanzasMovimiento::TIPO_GASTO => 'Gasto',
            FinanzasMovimiento::TIPO_TRANSFERENCIA => 'Transferencia',
            FinanzasMovimiento::TIPO_AJUSTE => 'Ajuste',
        ];
        $filas = [];
        $total = 0.0;

        foreach ($movimientos as $m) {
            $esGasto = $m->tipo === FinanzasMovimiento::TIPO_GASTO;
            $total += $esGasto ? -((float) $m->monto) : (float) $m->monto;
            $filas[] = [
                \Carbon\Carbon::parse($m->fecha)->format('d/m/Y'),
                $m->descripcion,
                $tipoLabels[$m->tipo] ?? ucfirst($m->tipo),
                $m->cuenta?->nombre ?? '—',
                $m->subcuenta?->nombre ?? '—',
                filled($m->categoria) ? $m->categoria : '—',
                ($esGasto ? '-' : '') . number_format((float) $m->monto, 2),
            ];
        }

        return [
            'titulo' => $titulo,
            'subtitulo' => $subtitulo,
            'columnas' => ['Fecha', 'Descripción', 'Tipo', 'Cuenta', 'Bolsillo', 'Categoría', "Monto ({$moneda})"],
            'filas' => $filas,
            'totales' => ['', '', '', '', '', 'Neto', number_format($total, 2)],
            'moneda' => $moneda,
        ];
    }

    protected function validateListRequest(Request $request): array
    {
        return $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:50'],
        ]);
    }

    protected function paginationMeta(LengthAwarePaginator $records): array
    {
        return [
            'current_page' => $records->currentPage(),
            'last_page' => $records->lastPage(),
            'per_page' => $records->perPage(),
            'total' => $records->total(),
            'from' => $records->firstItem(),
            'to' => $records->lastItem(),
        ];
    }

    protected function validatePropietario(Request $request, ?FinanzasPropietario $propietario = null): array
    {
        $xnovaId = $this->currentXnovaId();

        return $request->validate([
            'nombre' => ['required', 'string', 'max:120', Rule::unique('finanzas_propietarios', 'nombre')->where('xnova_id', $xnovaId)->ignore($propietario?->id)],
        ]) + [
            'xnova_id' => $xnovaId,
            'active' => true,
        ];
    }

    protected function propietariosBaseQuery(string $search = ''): Builder
    {
        return FinanzasPropietario::query()
            ->select(['id', 'nombre', 'created_at'])
            ->withCount('cuentas')
            ->where('xnova_id', $this->currentXnovaId())
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where('nombre', 'like', "%{$search}%");
            });
    }

    protected function cuentasBaseQuery(string $search = '', ?int $propietarioId = null): Builder
    {
        return FinanzasCuenta::query()
            ->with('propietario:id,nombre')
            ->where('xnova_id', $this->currentXnovaId())
            ->when($propietarioId, fn (Builder $query) => $query->where('finanzas_propietario_id', $propietarioId))
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('nombre', 'like', "%{$search}%")
                        ->orWhere('tipo', 'like', "%{$search}%")
                        ->orWhere('moneda', 'like', "%{$search}%")
                        ->orWhereHas('propietario', function (Builder $query) use ($search): void {
                            $query->where('nombre', 'like', "%{$search}%");
                        });
                });
            });
    }

    protected function ingresosFijosBaseQuery(string $search = '', ?int $propietarioId = null): Builder
    {
        return FinanzasIngresoFijo::query()
            ->with(['propietario:id,nombre', 'cuenta:id,nombre'])
            ->where('xnova_id', $this->currentXnovaId())
            ->when($propietarioId, fn (Builder $query) => $query->where('finanzas_propietario_id', $propietarioId))
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('nombre', 'like', "%{$search}%")
                        ->orWhereHas('propietario', function (Builder $query) use ($search): void {
                            $query->where('nombre', 'like', "%{$search}%");
                        })
                        ->orWhereHas('cuenta', function (Builder $query) use ($search): void {
                            $query->where('nombre', 'like', "%{$search}%");
                        });
                });
            });
    }

    protected function gastosFijosBaseQuery(string $search = '', ?int $propietarioId = null): Builder
    {
        return FinanzasGastoFijo::query()
            ->with('propietario:id,nombre')
            ->where('xnova_id', $this->currentXnovaId())
            ->when($propietarioId, fn (Builder $query) => $query->where('finanzas_propietario_id', $propietarioId))
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('nombre', 'like', "%{$search}%")
                        ->orWhereHas('propietario', function (Builder $query) use ($search): void {
                            $query->where('nombre', 'like', "%{$search}%");
                        });
                });
            });
    }

    protected function deudasBaseQuery(string $search = '', ?string $estado = null): Builder
    {
        return FinanzasDeuda::query()
            ->where('xnova_id', $this->currentXnovaId())
            ->when($estado === 'pendiente', fn (Builder $query) => $query->where('saldo_pendiente', '>', 0))
            ->when($estado === 'pagada', fn (Builder $query) => $query->where('saldo_pendiente', '<=', 0))
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('nombre', 'like', "%{$search}%")
                        ->orWhere('acreedor', 'like', "%{$search}%")
                        ->orWhere('categoria', 'like', "%{$search}%");
                });
            });
    }

    protected function cuentasPorCobrarBaseQuery(string $search = '', ?string $estado = null): Builder
    {
        return FinanzasCuentaPorCobrar::query()
            ->where('xnova_id', $this->currentXnovaId())
            ->when($estado === 'pendiente', fn (Builder $query) => $query->where('saldo_pendiente', '>', 0))
            ->when($estado === 'cobrada', fn (Builder $query) => $query->where('saldo_pendiente', '<=', 0))
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('nombre', 'like', "%{$search}%")
                        ->orWhere('deudor', 'like', "%{$search}%")
                        ->orWhere('categoria', 'like', "%{$search}%");
                });
            });
    }

    protected function movimientosBaseQuery(string $search = ''): Builder
    {
        return FinanzasMovimiento::query()
            ->with(['propietario:id,nombre', 'cuenta:id,nombre,moneda', 'cuentaDestino:id,nombre', 'subcuenta:id,nombre', 'subcuentaDestino:id,nombre'])
            ->where('xnova_id', $this->currentXnovaId())
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('descripcion', 'like', "%{$search}%")
                        ->orWhere('categoria', 'like', "%{$search}%")
                        ->orWhere('tipo', 'like', "%{$search}%")
                        ->orWhereHas('propietario', function (Builder $query) use ($search): void {
                            $query->where('nombre', 'like', "%{$search}%");
                        })
                        ->orWhereHas('cuenta', function (Builder $query) use ($search): void {
                            $query->where('nombre', 'like', "%{$search}%");
                        })
                        ->orWhereHas('cuentaDestino', function (Builder $query) use ($search): void {
                            $query->where('nombre', 'like', "%{$search}%");
                        });
                });
            });
    }

    protected function validateCuenta(Request $request, ?FinanzasCuenta $cuenta = null): array
    {
        $xnovaId = $this->currentXnovaId();
        $propietarioId = $request->filled('finanzas_propietario_id') ? (int) $request->input('finanzas_propietario_id') : null;

        return $request->validate([
            'finanzas_propietario_id' => ['nullable', 'integer', Rule::exists('finanzas_propietarios', 'id')->where('xnova_id', $xnovaId)],
            'nombre' => [
                'required',
                'string',
                'max:120',
                Rule::unique('finanzas_cuentas', 'nombre')
                    ->where('xnova_id', $xnovaId)
                    ->where('finanzas_propietario_id', $propietarioId)
                    ->ignore($cuenta?->id),
            ],
            'tipo' => ['required', 'string', 'max:40'],
            'icono' => ['nullable', 'string', 'max:60'],
            'moneda' => ['required', 'string', 'size:3'],
            'saldo_inicial' => ['nullable', 'numeric'],
            'active' => ['nullable', 'boolean'],
        ], [
            'nombre.unique' => 'Ya existe una cuenta con ese nombre para este propietario.',
        ]) + [
            'xnova_id' => $xnovaId,
            'active' => $request->boolean('active', true),
        ];
    }

    protected function validateFijo(Request $request, bool $withCategory = false): array
    {
        $xnovaId = $this->currentXnovaId();
        $rules = [
            'finanzas_propietario_id' => ['required', 'integer', Rule::exists('finanzas_propietarios', 'id')->where('xnova_id', $xnovaId)],
            'finanzas_cuenta_id' => ['nullable', 'integer', Rule::exists('finanzas_cuentas', 'id')->where('xnova_id', $xnovaId)],
            'nombre' => ['required', 'string', 'max:140'],
            'icono' => ['nullable', 'string', 'max:60'],
            'monto' => ['required', 'numeric', 'min:0.01'],
            'preguntar_al_usar' => ['nullable', 'boolean'],
            'active' => ['nullable', 'boolean'],
        ];

        if ($withCategory) {
            $rules['categoria'] = ['nullable', 'string', 'max:80'];
        }

        $data = $request->validate($rules);
        $data['preguntar_al_usar'] = $request->boolean('preguntar_al_usar', false);
        $data['active'] = $request->boolean('active', true);

        return $data + [
            'xnova_id' => $xnovaId,
            'frecuencia' => 'mensual',
            'dia_mes' => null,
        ];
    }

    protected function validateGastoFijo(Request $request): array
    {
        $xnovaId = $this->currentXnovaId();

        $data = $request->validate([
            'finanzas_propietario_id' => ['required', 'integer', Rule::exists('finanzas_propietarios', 'id')->where('xnova_id', $xnovaId)],
            'nombre' => ['required', 'string', 'max:140'],
            'icono' => ['nullable', 'string', 'max:60'],
            'monto' => ['required', 'numeric', 'min:0.01'],
            'preguntar_al_usar' => ['nullable', 'boolean'],
            'active' => ['nullable', 'boolean'],
        ]);

        $data['preguntar_al_usar'] = $request->boolean('preguntar_al_usar', false);
        $data['active'] = $request->boolean('active', true);

        return $data + [
            'xnova_id' => $xnovaId,
            'finanzas_cuenta_id' => null,
            'categoria' => null,
            'frecuencia' => 'mensual',
            'dia_mes' => null,
        ];
    }

    protected function validateDeuda(Request $request): array
    {
        $xnovaId = $this->currentXnovaId();

        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:140'],
            'acreedor' => ['nullable', 'string', 'max:140'],
            'icono' => ['nullable', 'string', 'max:60'],
            'categoria' => ['nullable', 'string', 'max:80'],
            'monto_original' => ['required', 'numeric', 'min:0.01'],
            'fecha' => ['required', 'date'],
            'fecha_vencimiento' => ['nullable', 'date', 'after_or_equal:fecha'],
            'notas' => ['nullable', 'string', 'max:240'],
            'active' => ['nullable', 'boolean'],
        ]);

        $data['active'] = $request->boolean('active', true);

        return $data + [
            'xnova_id' => $xnovaId,
        ];
    }

    protected function validateCuentaPorCobrar(Request $request): array
    {
        $xnovaId = $this->currentXnovaId();

        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:140'],
            'deudor' => ['nullable', 'string', 'max:140'],
            'icono' => ['nullable', 'string', 'max:60'],
            'categoria' => ['nullable', 'string', 'max:80'],
            'monto_original' => ['required', 'numeric', 'min:0.01'],
            'fecha' => ['required', 'date'],
            'fecha_vencimiento' => ['nullable', 'date', 'after_or_equal:fecha'],
            'notas' => ['nullable', 'string', 'max:240'],
            'active' => ['nullable', 'boolean'],
        ]);

        $data['active'] = $request->boolean('active', true);

        return $data + [
            'xnova_id' => $xnovaId,
        ];
    }

    protected function validateMovimiento(Request $request): array
    {
        $xnovaId = $this->currentXnovaId();
        $data = $request->validate([
            'finanzas_cuenta_id' => ['required', 'integer', Rule::exists('finanzas_cuentas', 'id')->where('xnova_id', $xnovaId)],
            'finanzas_cuenta_destino_id' => ['nullable', 'integer', Rule::exists('finanzas_cuentas', 'id')->where('xnova_id', $xnovaId)],
            'finanzas_subcuenta_id' => ['nullable', 'integer', Rule::exists('finanzas_subcuentas', 'id')->where('xnova_id', $xnovaId)],
            'finanzas_subcuenta_destino_id' => ['nullable', 'integer', Rule::exists('finanzas_subcuentas', 'id')->where('xnova_id', $xnovaId)],
            'finanzas_propietario_id' => ['nullable', 'integer', Rule::exists('finanzas_propietarios', 'id')->where('xnova_id', $xnovaId)],
            'tipo' => ['required', Rule::in([FinanzasMovimiento::TIPO_INGRESO, FinanzasMovimiento::TIPO_GASTO, FinanzasMovimiento::TIPO_TRANSFERENCIA, FinanzasMovimiento::TIPO_AJUSTE])],
            'descripcion' => ['required', 'string', 'max:180'],
            'categoria' => ['nullable', 'string', 'max:80'],
            'monto' => ['required', 'numeric', 'min:0.01'],
            'fecha' => ['required', 'date'],
        ]);

        $cuenta = FinanzasCuenta::query()
            ->where('xnova_id', $xnovaId)
            ->findOrFail((int) $data['finanzas_cuenta_id']);

        $data['finanzas_subcuenta_id'] = $this->resolveSubcuentaIdForCuenta(
            $cuenta,
            (int) ($data['finanzas_subcuenta_id'] ?? 0)
        );

        if ($data['tipo'] === FinanzasMovimiento::TIPO_TRANSFERENCIA) {
            $request->validate([
                'finanzas_cuenta_destino_id' => ['required', 'integer', 'different:finanzas_cuenta_id'],
            ]);

            $cuentaDestino = FinanzasCuenta::query()
                ->where('xnova_id', $xnovaId)
                ->findOrFail((int) $data['finanzas_cuenta_destino_id']);

            $data['finanzas_subcuenta_destino_id'] = $this->resolveSubcuentaIdForCuenta(
                $cuentaDestino,
                (int) ($data['finanzas_subcuenta_destino_id'] ?? 0)
            );
        } else {
            $data['finanzas_cuenta_destino_id'] = null;
            $data['finanzas_subcuenta_destino_id'] = null;
        }

        return $data + ['xnova_id' => $xnovaId];
    }

    protected function validateBolsillo(Request $request, FinanzasCuenta $cuenta, ?FinanzasSubcuenta $bolsillo = null): array
    {
        return $request->validate([
            'nombre' => [
                'required',
                'string',
                'max:120',
                Rule::unique('finanzas_subcuentas', 'nombre')
                    ->where('finanzas_cuenta_id', $cuenta->id)
                    ->ignore($bolsillo?->id),
            ],
            'icono' => ['nullable', 'string', 'max:60'],
            'meta' => ['nullable', 'numeric', 'min:0'],
        ]) + [
            'xnova_id' => $this->currentXnovaId(),
            'finanzas_cuenta_id' => $cuenta->id,
            'active' => true,
        ];
    }

    protected function propietariosActivos(int $xnovaId)
    {
        return FinanzasPropietario::query()
            ->where('xnova_id', $xnovaId)
            ->where('active', true)
            ->orderBy('nombre')
            ->get(['id', 'nombre']);
    }

    protected function cuentasActivas(int $xnovaId)
    {
        return FinanzasCuenta::query()
            ->where('xnova_id', $xnovaId)
            ->where('active', true)
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'finanzas_propietario_id', 'tipo', 'moneda']);
    }

    protected function subcuentasActivas(int $xnovaId)
    {
        return FinanzasSubcuenta::query()
            ->with('cuenta:id,nombre')
            ->where('xnova_id', $xnovaId)
            ->where('active', true)
            ->orderBy('finanzas_cuenta_id')
            ->orderByDesc('es_general')
            ->orderBy('nombre')
            ->get(['id', 'finanzas_cuenta_id', 'nombre', 'es_general']);
    }

    protected function editingRecord(string $modelClass, int $id): ?Model
    {
        if ($id <= 0) {
            return null;
        }

        $record = $modelClass::query()->find($id);

        if (! $record || (int) $record->xnova_id !== $this->currentXnovaId()) {
            return null;
        }

        return $record;
    }

    protected function ensureCurrentXnova(Model $model): void
    {
        abort_if((int) $model->xnova_id !== $this->currentXnovaId(), 404);
    }

    protected function ensureBolsilloBelongsToCuenta(FinanzasCuenta $cuenta, FinanzasSubcuenta $bolsillo): void
    {
        $this->ensureCurrentXnova($cuenta);
        $this->ensureCurrentXnova($bolsillo);
        abort_if((int) $bolsillo->finanzas_cuenta_id !== (int) $cuenta->id, 404);
    }

    protected function generalSubcuenta(FinanzasCuenta $cuenta): FinanzasSubcuenta
    {
        return FinanzasSubcuenta::query()->firstOrCreate(
            [
                'finanzas_cuenta_id' => $cuenta->id,
                'es_general' => true,
            ],
            [
                'xnova_id' => $cuenta->xnova_id,
                'nombre' => 'General',
                'meta' => null,
                'active' => true,
            ],
        );
    }

    protected function resolveSubcuentaIdForCuenta(FinanzasCuenta $cuenta, int $subcuentaId = 0): int
    {
        if ($subcuentaId > 0) {
            $belongsToCuenta = FinanzasSubcuenta::query()
                ->where('xnova_id', $this->currentXnovaId())
                ->where('finanzas_cuenta_id', $cuenta->id)
                ->whereKey($subcuentaId)
                ->exists();

            if ($belongsToCuenta) {
                return $subcuentaId;
            }
        }

        return $this->generalSubcuenta($cuenta)->id;
    }

    protected function balancesPorCuenta(int $xnovaId, array $cuentaIds): array
    {
        $balances = array_fill_keys($cuentaIds, 0.0);

        if ($cuentaIds === []) {
            return $balances;
        }

        $movimientos = FinanzasMovimiento::query()
            ->where('xnova_id', $xnovaId)
            ->whereNull('anulado_en')
            ->where('pendiente', false)
            ->where(function ($query) use ($cuentaIds): void {
                $query->whereIn('finanzas_cuenta_id', $cuentaIds)
                    ->orWhereIn('finanzas_cuenta_destino_id', $cuentaIds);
            })
            ->get(['finanzas_cuenta_id', 'finanzas_cuenta_destino_id', 'tipo', 'monto']);

        foreach ($movimientos as $movimiento) {
            $monto = (float) $movimiento->monto;

            if (in_array($movimiento->tipo, [FinanzasMovimiento::TIPO_INGRESO, FinanzasMovimiento::TIPO_AJUSTE], true)) {
                $balances[$movimiento->finanzas_cuenta_id] = ($balances[$movimiento->finanzas_cuenta_id] ?? 0) + $monto;
            }

            if ($movimiento->tipo === FinanzasMovimiento::TIPO_GASTO) {
                $balances[$movimiento->finanzas_cuenta_id] = ($balances[$movimiento->finanzas_cuenta_id] ?? 0) - $monto;
            }

            if ($movimiento->tipo === FinanzasMovimiento::TIPO_TRANSFERENCIA) {
                $balances[$movimiento->finanzas_cuenta_id] = ($balances[$movimiento->finanzas_cuenta_id] ?? 0) - $monto;

                if ($movimiento->finanzas_cuenta_destino_id) {
                    $balances[$movimiento->finanzas_cuenta_destino_id] = ($balances[$movimiento->finanzas_cuenta_destino_id] ?? 0) + $monto;
                }
            }
        }

        return $balances;
    }

    protected function balancesPorSubcuenta(int $xnovaId, array $subcuentaIds): array
    {
        $balances = array_fill_keys($subcuentaIds, 0.0);

        if ($subcuentaIds === []) {
            return $balances;
        }

        $movimientos = FinanzasMovimiento::query()
            ->where('xnova_id', $xnovaId)
            ->whereNull('anulado_en')
            ->where('pendiente', false)
            ->where(function ($query) use ($subcuentaIds): void {
                $query->whereIn('finanzas_subcuenta_id', $subcuentaIds)
                    ->orWhereIn('finanzas_subcuenta_destino_id', $subcuentaIds);
            })
            ->get(['finanzas_subcuenta_id', 'finanzas_subcuenta_destino_id', 'tipo', 'monto']);

        foreach ($movimientos as $movimiento) {
            $monto = (float) $movimiento->monto;

            if (in_array($movimiento->tipo, [FinanzasMovimiento::TIPO_INGRESO, FinanzasMovimiento::TIPO_AJUSTE], true)) {
                $balances[$movimiento->finanzas_subcuenta_id] = ($balances[$movimiento->finanzas_subcuenta_id] ?? 0) + $monto;
            }

            if ($movimiento->tipo === FinanzasMovimiento::TIPO_GASTO) {
                $balances[$movimiento->finanzas_subcuenta_id] = ($balances[$movimiento->finanzas_subcuenta_id] ?? 0) - $monto;
            }

            if ($movimiento->tipo === FinanzasMovimiento::TIPO_TRANSFERENCIA) {
                $balances[$movimiento->finanzas_subcuenta_id] = ($balances[$movimiento->finanzas_subcuenta_id] ?? 0) - $monto;

                if ($movimiento->finanzas_subcuenta_destino_id) {
                    $balances[$movimiento->finanzas_subcuenta_destino_id] = ($balances[$movimiento->finanzas_subcuenta_destino_id] ?? 0) + $monto;
                }
            }
        }

        return $balances;
    }

    /**
     * Estructura de cuentas activas -> bolsillos (con su saldo disponible) para los asistentes
     * de pago de deuda y cobro de cuentas por cobrar.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function bolsillosDisponiblesParaSeleccion(int $xnovaId): array
    {
        $cuentas = FinanzasCuenta::query()
            ->where('xnova_id', $xnovaId)
            ->where('active', true)
            ->with([
                'propietario:id,nombre',
                'subcuentas' => fn ($query) => $query->where('active', true)
                    ->orderByDesc('es_general')
                    ->orderBy('nombre'),
            ])
            ->orderBy('nombre')
            ->get()
            ->filter(fn ($c) => $c->subcuentas->isNotEmpty());

        $subcuentaIds = $cuentas->flatMap(fn ($c) => $c->subcuentas->pluck('id'))->all();
        $balances = $this->balancesPorSubcuenta($xnovaId, $subcuentaIds);

        return $cuentas->map(fn ($c) => [
            'id' => $c->id,
            'nombre' => $c->nombre,
            'tipo' => $c->tipo,
            'moneda' => $c->moneda,
            'icono' => $c->icono,
            'propietario_id' => $c->finanzas_propietario_id,
            'propietario_nombre' => $c->propietario?->nombre,
            'bolsillos' => $c->subcuentas->map(fn ($b) => [
                'id' => $b->id,
                'nombre' => $b->nombre,
                'icono' => $b->icono,
                'es_general' => (bool) $b->es_general,
                'saldo' => (float) ($balances[$b->id] ?? 0),
            ])->values()->all(),
        ])->values()->all();
    }

    /**
     * Lista única de propietarios a partir de la estructura de cuentas/bolsillos.
     *
     * @param  array<int, array<string, mixed>>  $cuentas
     * @return array<int, array<string, mixed>>
     */
    protected function propietariosDeCuentas(array $cuentas): array
    {
        return collect($cuentas)
            ->map(fn ($c) => [
                'id' => $c['propietario_id'],
                'nombre' => $c['propietario_nombre'] ?? 'Sin propietario',
            ])
            ->unique(fn ($p) => (string) ($p['id'] ?? 'null'))
            ->values()
            ->all();
    }
}

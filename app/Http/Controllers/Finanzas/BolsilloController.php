<?php

namespace App\Http\Controllers\Finanzas;

use App\Http\Requests\Finanzas\BolsilloRequest;
use App\Http\Requests\Finanzas\DistribuirBolsillosRequest;
use App\Models\FinanzasCuenta;
use App\Models\FinanzasSubcuenta;
use App\Services\Finanzas\BolsilloService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class BolsilloController extends BaseFinanzasController
{
    public function __construct(private readonly BolsilloService $bolsillos) {}

    public function create(FinanzasCuenta $cuenta): View
    {
        $this->authorize('update', $cuenta);

        return view('finanzas.cuentas.bolsillo-form', [
            'cuenta' => $cuenta,
            'bolsillo' => new FinanzasSubcuenta(['active' => true]),
            'isEditing' => false,
        ]);
    }

    public function store(BolsilloRequest $request, FinanzasCuenta $cuenta): RedirectResponse
    {
        $this->authorize('update', $cuenta);

        $this->bolsillos->crear($cuenta, $request->atributos());

        return redirect()->route('finanzas.cuentas.show', $cuenta)->with('status', 'Bolsillo guardado correctamente.');
    }

    public function edit(FinanzasCuenta $cuenta, FinanzasSubcuenta $bolsillo): View
    {
        $this->asegurarBolsilloDeCuenta($cuenta, $bolsillo);

        return view('finanzas.cuentas.bolsillo-form', [
            'cuenta' => $cuenta,
            'bolsillo' => $bolsillo,
            'isEditing' => true,
        ]);
    }

    public function update(BolsilloRequest $request, FinanzasCuenta $cuenta, FinanzasSubcuenta $bolsillo): RedirectResponse
    {
        $this->asegurarBolsilloDeCuenta($cuenta, $bolsillo);

        $bolsillo->update($request->atributos());

        return redirect()->route('finanzas.cuentas.show', $cuenta)->with('status', 'Bolsillo actualizado correctamente.');
    }

    public function deactivate(FinanzasCuenta $cuenta, FinanzasSubcuenta $bolsillo): RedirectResponse
    {
        $this->asegurarBolsilloDeCuenta($cuenta, $bolsillo);

        $this->bolsillos->desactivar($bolsillo);

        return redirect()->route('finanzas.cuentas.show', $cuenta)->with('status', 'Bolsillo desactivado correctamente.');
    }

    public function distribute(DistribuirBolsillosRequest $request, FinanzasCuenta $cuenta): RedirectResponse
    {
        $this->authorize('update', $cuenta);

        $this->bolsillos->distribuir($cuenta, $request->toDto());

        return redirect()->route('finanzas.cuentas.show', $cuenta)->with('status', 'Distribucion guardada correctamente.');
    }
}

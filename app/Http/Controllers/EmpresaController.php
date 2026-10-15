<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\UsaEmpresaActual;
use App\Models\Xnova;
use App\Services\PermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmpresaController extends Controller
{
    use UsaEmpresaActual;

    public const MONEDAS_DISPONIBLES = [
        'LPS' => 'Lempiras (LPS)',
        'USD' => 'Dólares (USD)',
        'EUR' => 'Euros (EUR)',
        'MXN' => 'Pesos mexicanos (MXN)',
        'GTQ' => 'Quetzales (GTQ)',
    ];

    public function __construct(protected PermissionService $permissionService)
    {
    }

    public function preferencias(Request $request): View
    {
        $this->autorizarCreador($request);

        $xnova = Xnova::query()->findOrFail($this->empresaId());

        return view('empresa.preferencias', [
            'xnova' => $xnova,
            'monedas' => self::MONEDAS_DISPONIBLES,
        ]);
    }

    public function actualizarPreferencias(Request $request): RedirectResponse
    {
        $this->autorizarCreador($request);

        $data = $request->validate([
            'moneda_default' => ['required', 'string', 'size:3', 'in:'.implode(',', array_keys(self::MONEDAS_DISPONIBLES))],
        ], [
            'moneda_default.required' => 'Selecciona una moneda predeterminada.',
            'moneda_default.in' => 'La moneda seleccionada no es válida.',
        ]);

        $xnova = Xnova::query()->findOrFail($this->empresaId());
        $xnova->update($data);

        return redirect()->route('empresa.preferencias')->with('status', 'Preferencias guardadas correctamente.');
    }

    protected function autorizarCreador(Request $request): void
    {
        abort_unless(
            $this->permissionService->isCompanyCreator($request->user()),
            403,
            'Solo el administrador (creador de la empresa) puede modificar las preferencias.'
        );
    }
}

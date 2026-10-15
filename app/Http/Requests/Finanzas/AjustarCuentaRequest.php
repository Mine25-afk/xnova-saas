<?php

namespace App\Http\Requests\Finanzas;

use App\DTOs\Finanzas\AjusteSaldoData;
use App\Http\Requests\TenantFormRequest;
use App\Models\FinanzasCuenta;

class AjustarCuentaRequest extends TenantFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $cuenta = $this->route('cuenta');

        return [
            'saldo_real' => ['required', 'numeric'],
            'ajustes' => ['required', 'array', 'min:1'],
            'ajustes.*.finanzas_subcuenta_id' => [
                'required',
                'integer',
                $this->existeEnEmpresa('finanzas_subcuentas')
                    ->where('finanzas_cuenta_id', $cuenta instanceof FinanzasCuenta ? $cuenta->id : 0)
                    ->where('active', true),
            ],
            'ajustes.*.monto' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['saldo_real' => 'saldo real'];
    }

    public function toDto(): AjusteSaldoData
    {
        $asignaciones = [];

        foreach ((array) $this->validated('ajustes', []) as $ajuste) {
            $asignaciones[(int) $ajuste['finanzas_subcuenta_id']] = (float) ($ajuste['monto'] ?? 0);
        }

        return new AjusteSaldoData(
            saldoReal: (float) $this->validated('saldo_real'),
            asignaciones: $asignaciones,
        );
    }
}

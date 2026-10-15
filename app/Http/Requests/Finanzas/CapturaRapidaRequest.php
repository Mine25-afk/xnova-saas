<?php

namespace App\Http\Requests\Finanzas;

use App\Http\Requests\TenantFormRequest;
use App\Models\FinanzasMovimiento;
use Illuminate\Validation\Rule;

class CapturaRapidaRequest extends TenantFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'tipo' => ['required', Rule::in([FinanzasMovimiento::TIPO_INGRESO, FinanzasMovimiento::TIPO_GASTO])],
            'finanzas_cuenta_id' => ['required', 'integer', $this->existeEnEmpresa('finanzas_cuentas')],
            'finanzas_subcuenta_id' => ['nullable', 'integer', $this->existeEnEmpresa('finanzas_subcuentas')],
            'monto' => ['required', 'numeric', 'min:0.01'],
            'descripcion' => ['nullable', 'string', 'max:180'],
            'categoria' => ['nullable', 'string', 'max:80'],
            'fecha' => ['nullable', 'date'],
        ];
    }
}

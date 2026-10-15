<?php

namespace App\Http\Requests\Finanzas;

use App\DTOs\Finanzas\PagoData;
use App\Http\Requests\TenantFormRequest;

/**
 * Abono a una deuda o cobro de una cuenta por cobrar eligiendo el bolsillo.
 */
class PagoRequest extends TenantFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'finanzas_subcuenta_id' => ['required', 'integer', $this->existeEnEmpresa('finanzas_subcuentas')],
            'monto' => ['required', 'numeric', 'min:0.01'],
            'pendiente' => ['nullable', 'boolean'],
        ];
    }

    public function toDto(): PagoData
    {
        return new PagoData(
            subcuentaId: $this->integer('finanzas_subcuenta_id'),
            monto: (float) $this->validated('monto'),
            pendiente: $this->boolean('pendiente'),
        );
    }
}

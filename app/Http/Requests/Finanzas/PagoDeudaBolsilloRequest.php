<?php

namespace App\Http\Requests\Finanzas;

use App\Http\Requests\TenantFormRequest;

/**
 * Abono a una deuda desde la pantalla de un bolsillo (el bolsillo viene en la ruta).
 */
class PagoDeudaBolsilloRequest extends TenantFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'finanzas_deuda_id' => ['required', 'integer', $this->existeEnEmpresa('finanzas_deudas')],
            'monto' => ['required', 'numeric', 'min:0.01'],
            'pendiente' => ['nullable', 'boolean'],
        ];
    }

    public function deudaId(): int
    {
        return $this->integer('finanzas_deuda_id');
    }

    public function monto(): float
    {
        return (float) $this->validated('monto');
    }

    public function pendiente(): bool
    {
        return $this->boolean('pendiente');
    }
}

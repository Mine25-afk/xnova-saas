<?php

namespace App\Http\Requests\Finanzas;

use App\Http\Requests\TenantFormRequest;

class IngresoFijoRequest extends TenantFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'finanzas_propietario_id' => ['required', 'integer', $this->existeEnEmpresa('finanzas_propietarios')],
            'finanzas_cuenta_id' => ['nullable', 'integer', $this->existeEnEmpresa('finanzas_cuentas')],
            'nombre' => ['required', 'string', 'max:140'],
            'icono' => ['nullable', 'string', 'max:60'],
            'monto' => ['required', 'numeric', 'min:0.01'],
            'preguntar_al_usar' => ['nullable', 'boolean'],
            'active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function atributos(): array
    {
        return array_merge($this->validated(), [
            'preguntar_al_usar' => $this->preguntarAlUsar(),
            'active' => $this->boolean('active', true),
            'frecuencia' => 'mensual',
            'dia_mes' => null,
        ]);
    }

    public function preguntarAlUsar(): bool
    {
        return $this->boolean('preguntar_al_usar');
    }

    public function monto(): float
    {
        return (float) $this->validated('monto');
    }
}

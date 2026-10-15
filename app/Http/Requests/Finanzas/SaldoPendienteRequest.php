<?php

namespace App\Http\Requests\Finanzas;

use App\Http\Requests\TenantFormRequest;

/**
 * Base de deudas y cuentas por cobrar: un monto original que se va saldando.
 */
abstract class SaldoPendienteRequest extends TenantFormRequest
{
    /**
     * Nombre del campo con la contraparte (acreedor o deudor).
     */
    abstract protected function campoContraparte(): string;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:140'],
            $this->campoContraparte() => ['nullable', 'string', 'max:140'],
            'icono' => ['nullable', 'string', 'max:60'],
            'categoria' => ['nullable', 'string', 'max:80'],
            'monto_original' => ['required', 'numeric', 'min:0.01'],
            'fecha' => ['required', 'date'],
            'fecha_vencimiento' => ['nullable', 'date', 'after_or_equal:fecha'],
            'notas' => ['nullable', 'string', 'max:240'],
            'active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function atributos(): array
    {
        return array_merge($this->validated(), [
            'active' => $this->boolean('active', true),
        ]);
    }
}

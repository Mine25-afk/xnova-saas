<?php

namespace App\Http\Requests\Finanzas;

use App\Http\Requests\TenantFormRequest;

/**
 * Ingreso o gasto rápido desde la pantalla de un bolsillo, opcionalmente
 * ligado a un ingreso/gasto fijo "por pagos".
 */
abstract class MovimientoBolsilloRequest extends TenantFormRequest
{
    abstract protected function tablaFijo(): string;

    abstract public function campoFijo(): string;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:180'],
            'monto' => ['required', 'numeric', 'min:0.01'],
            $this->campoFijo() => ['nullable', 'integer', $this->existeEnEmpresa($this->tablaFijo())],
        ];
    }

    public function descripcion(): string
    {
        return (string) $this->validated('nombre');
    }

    public function monto(): float
    {
        return (float) $this->validated('monto');
    }

    public function fijoId(): ?int
    {
        return $this->enteroOpcional($this->campoFijo());
    }
}

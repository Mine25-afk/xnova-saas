<?php

namespace App\Http\Requests\Finanzas;

use App\Http\Requests\TenantFormRequest;
use App\Models\FinanzasCuenta;

class CuentaRequest extends TenantFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $cuenta = $this->route('cuenta');

        return [
            'finanzas_propietario_id' => ['nullable', 'integer', $this->existeEnEmpresa('finanzas_propietarios')],
            'nombre' => [
                'required',
                'string',
                'max:120',
                $this->unicoEnEmpresa('finanzas_cuentas', 'nombre')
                    ->where('finanzas_propietario_id', $this->enteroOpcional('finanzas_propietario_id'))
                    ->ignore($cuenta instanceof FinanzasCuenta ? $cuenta->id : null),
            ],
            'tipo' => ['required', 'string', 'max:40'],
            'icono' => ['nullable', 'string', 'max:60'],
            'moneda' => ['required', 'string', 'size:3'],
            'saldo_inicial' => ['nullable', 'numeric'],
            'active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nombre.unique' => 'Ya existe una cuenta con ese nombre para este propietario.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function atributos(): array
    {
        $datos = $this->safe()->except(['saldo_inicial']);
        $datos['active'] = $this->boolean('active', true);

        return $datos;
    }

    public function saldoInicial(): float
    {
        return (float) $this->validated('saldo_inicial', 0);
    }
}

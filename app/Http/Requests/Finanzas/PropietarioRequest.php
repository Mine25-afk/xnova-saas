<?php

namespace App\Http\Requests\Finanzas;

use App\Http\Requests\TenantFormRequest;
use App\Models\FinanzasPropietario;

class PropietarioRequest extends TenantFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $propietario = $this->route('propietario');

        return [
            'nombre' => [
                'required',
                'string',
                'max:120',
                $this->unicoEnEmpresa('finanzas_propietarios', 'nombre')
                    ->ignore($propietario instanceof FinanzasPropietario ? $propietario->id : null),
            ],
        ];
    }

    /**
     * @return array{nombre: string, active: bool}
     */
    public function atributos(): array
    {
        return [
            'nombre' => (string) $this->validated('nombre'),
            'active' => true,
        ];
    }
}

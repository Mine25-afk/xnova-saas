<?php

namespace App\Http\Requests\Finanzas;

use App\Http\Requests\TenantFormRequest;
use App\Models\FinanzasCuenta;
use App\Models\FinanzasSubcuenta;
use Illuminate\Validation\Rule;

class BolsilloRequest extends TenantFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $cuenta = $this->route('cuenta');
        $bolsillo = $this->route('bolsillo');

        return [
            'nombre' => [
                'required',
                'string',
                'max:120',
                Rule::unique('finanzas_subcuentas', 'nombre')
                    ->where('finanzas_cuenta_id', $cuenta instanceof FinanzasCuenta ? $cuenta->id : 0)
                    ->ignore($bolsillo instanceof FinanzasSubcuenta ? $bolsillo->id : null),
            ],
            'icono' => ['nullable', 'string', 'max:60'],
            'meta' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function atributos(): array
    {
        return $this->validated() + ['active' => true];
    }
}

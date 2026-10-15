<?php

namespace App\Http\Requests\Finanzas;

use App\DTOs\Finanzas\TransferenciaData;
use App\Http\Requests\TenantFormRequest;

class TransferenciaRequest extends TenantFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'finanzas_subcuenta_destino_id' => [
                'required_without:crear_bolsillo_espejo',
                'nullable',
                'integer',
                $this->existeEnEmpresa('finanzas_subcuentas')->where('active', true),
            ],
            'crear_bolsillo_espejo' => ['nullable', 'boolean'],
            'finanzas_cuenta_destino_id' => [
                'required_if:crear_bolsillo_espejo,true',
                'nullable',
                'integer',
                $this->existeEnEmpresa('finanzas_cuentas')->where('active', true),
            ],
            'monto' => ['required', 'numeric', 'min:0.01'],
            'descripcion' => ['nullable', 'string', 'max:180'],
            'pendiente' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'finanzas_subcuenta_destino_id.required_without' => 'Selecciona el bolsillo destino.',
        ];
    }

    public function toDto(): TransferenciaData
    {
        $descripcion = trim((string) $this->validated('descripcion'));

        return new TransferenciaData(
            monto: (float) $this->validated('monto'),
            subcuentaDestinoId: $this->enteroOpcional('finanzas_subcuenta_destino_id'),
            crearBolsilloEspejo: $this->boolean('crear_bolsillo_espejo'),
            cuentaDestinoId: $this->enteroOpcional('finanzas_cuenta_destino_id'),
            descripcion: $descripcion !== '' ? $descripcion : null,
            pendiente: $this->boolean('pendiente'),
        );
    }
}

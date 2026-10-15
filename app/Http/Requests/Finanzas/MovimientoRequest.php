<?php

namespace App\Http\Requests\Finanzas;

use App\DTOs\Finanzas\MovimientoData;
use App\Http\Requests\TenantFormRequest;
use App\Models\FinanzasMovimiento;
use Illuminate\Validation\Rule;

class MovimientoRequest extends TenantFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'finanzas_cuenta_id' => ['required', 'integer', $this->existeEnEmpresa('finanzas_cuentas')],
            'finanzas_cuenta_destino_id' => [
                'exclude_unless:tipo,'.FinanzasMovimiento::TIPO_TRANSFERENCIA,
                'required',
                'integer',
                'different:finanzas_cuenta_id',
                $this->existeEnEmpresa('finanzas_cuentas'),
            ],
            'finanzas_subcuenta_id' => ['nullable', 'integer', $this->existeEnEmpresa('finanzas_subcuentas')],
            'finanzas_subcuenta_destino_id' => [
                'exclude_unless:tipo,'.FinanzasMovimiento::TIPO_TRANSFERENCIA,
                'nullable',
                'integer',
                $this->existeEnEmpresa('finanzas_subcuentas'),
            ],
            'finanzas_propietario_id' => ['nullable', 'integer', $this->existeEnEmpresa('finanzas_propietarios')],
            'tipo' => ['required', Rule::in(array_keys(FinanzasMovimiento::TIPOS))],
            'descripcion' => ['required', 'string', 'max:180'],
            'categoria' => ['nullable', 'string', 'max:80'],
            'monto' => ['required', 'numeric', 'min:0.01'],
            'fecha' => ['required', 'date'],
        ];
    }

    public function toDto(): MovimientoData
    {
        $tipo = (string) $this->validated('tipo');
        $esTransferencia = $tipo === FinanzasMovimiento::TIPO_TRANSFERENCIA;

        return new MovimientoData(
            tipo: $tipo,
            cuentaId: $this->integer('finanzas_cuenta_id'),
            monto: (float) $this->validated('monto'),
            descripcion: (string) $this->validated('descripcion'),
            fecha: (string) $this->validated('fecha'),
            subcuentaId: $this->enteroOpcional('finanzas_subcuenta_id'),
            cuentaDestinoId: $esTransferencia ? $this->enteroOpcional('finanzas_cuenta_destino_id') : null,
            subcuentaDestinoId: $esTransferencia ? $this->enteroOpcional('finanzas_subcuenta_destino_id') : null,
            propietarioId: $this->enteroOpcional('finanzas_propietario_id'),
            categoria: $this->validated('categoria'),
        );
    }
}

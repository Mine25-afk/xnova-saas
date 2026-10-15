<?php

namespace App\Http\Requests\Citas;

use App\Http\Requests\TenantFormRequest;

class HuecosLibresRequest extends TenantFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'colaborador_dni' => ['required', $this->existeEnEmpresa('colaboradores', 'dni')],
            'sucursal_Id' => ['required', 'integer', $this->existeEnEmpresa('sucursales')],
            'fecha' => ['required', 'date', 'after_or_equal:'.now()->toDateString()],
            'duracion' => ['nullable', 'integer', 'min:15', 'max:240'],
        ];
    }

    public function duracion(): int
    {
        return (int) ($this->validated('duracion') ?? 60);
    }
}

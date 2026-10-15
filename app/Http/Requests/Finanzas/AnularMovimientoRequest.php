<?php

namespace App\Http\Requests\Finanzas;

use App\Http\Requests\TenantFormRequest;

class AnularMovimientoRequest extends TenantFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'motivo' => ['required', 'string', 'min:3', 'max:240'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'motivo.required' => 'Indica un motivo para anular el movimiento.',
            'motivo.min' => 'El motivo debe tener al menos 3 caracteres.',
        ];
    }

    public function motivo(): string
    {
        return (string) $this->validated('motivo');
    }
}

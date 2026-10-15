<?php

namespace App\Http\Requests\Finanzas;

use App\Http\Requests\TenantFormRequest;

class CancelarPendienteRequest extends TenantFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'motivo' => ['nullable', 'string', 'max:240'],
        ];
    }

    public function motivo(): ?string
    {
        $motivo = $this->validated('motivo');

        return is_string($motivo) ? $motivo : null;
    }
}

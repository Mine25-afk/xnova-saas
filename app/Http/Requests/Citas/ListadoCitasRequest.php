<?php

namespace App\Http\Requests\Citas;

use App\Http\Requests\ListadoRequest;
use App\Models\Cita;
use App\Services\Citas\VistasRapidas;
use Illuminate\Validation\Rule;

class ListadoCitasRequest extends ListadoRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return parent::rules() + [
            'estado_id' => ['nullable', 'integer', Rule::in([
                Cita::ESTADO_RESERVADA_ID,
                Cita::ESTADO_TERMINADA_ID,
                Cita::ESTADO_CANCELADA_ID,
                Cita::ESTADO_AUTORIZADA_ID,
            ])],
            'vista' => ['nullable', 'string', Rule::in(array_keys(app(VistasRapidas::class)->definiciones()))],
        ];
    }

    public function vista(): string
    {
        return app(VistasRapidas::class)->resolver($this->validated('vista'));
    }

    public function estadoId(): int
    {
        return app(VistasRapidas::class)->estadoEfectivo((int) $this->validated('estado_id'), $this->vista());
    }
}

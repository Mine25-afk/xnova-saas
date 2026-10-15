<?php

namespace App\Http\Requests\Finanzas;

use App\DTOs\Finanzas\ReporteFiltros;
use App\Http\Requests\TenantFormRequest;
use App\Models\FinanzasMovimiento;
use Illuminate\Validation\Rule;

class ReporteRequest extends TenantFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'fecha_inicio' => ['nullable', 'date'],
            'fecha_fin' => ['nullable', 'date'],
            'cuenta' => ['nullable', 'integer'],
            'propietario' => ['nullable', 'integer'],
            'tipo' => ['nullable', Rule::in(array_keys(FinanzasMovimiento::TIPOS))],
            'print' => ['nullable', 'boolean'],
        ];
    }

    public function filtros(): ReporteFiltros
    {
        $tipo = $this->validated('tipo');

        return new ReporteFiltros(
            fechaInicio: $this->filled('fecha_inicio') ? (string) $this->validated('fecha_inicio') : null,
            fechaFin: $this->filled('fecha_fin') ? (string) $this->validated('fecha_fin') : null,
            cuentaId: $this->enteroOpcional('cuenta'),
            propietarioId: $this->enteroOpcional('propietario'),
            tipo: is_string($tipo) && $tipo !== '' ? $tipo : null,
        );
    }
}

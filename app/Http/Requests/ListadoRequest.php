<?php

namespace App\Http\Requests;

/**
 * Parámetros comunes de los listados paginados (búsqueda y tamaño de página).
 */
class ListadoRequest extends TenantFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:50'],
            'propietario_id' => ['nullable', 'integer'],
            'estado' => ['nullable', 'string', 'max:20'],
        ];
    }

    public function busqueda(): string
    {
        return trim((string) $this->validated('search'));
    }

    public function porPagina(): int
    {
        return (int) ($this->validated('per_page') ?? 10);
    }

    public function propietarioId(): ?int
    {
        return $this->enteroOpcional('propietario_id');
    }

    /**
     * @param  list<string>  $permitidos
     */
    public function estado(array $permitidos): ?string
    {
        $estado = $this->validated('estado');

        return in_array($estado, $permitidos, true) ? $estado : null;
    }
}

<?php

namespace App\Http\Requests\Finanzas;

use App\DTOs\Finanzas\DistribucionData;
use App\Http\Requests\TenantFormRequest;
use App\Models\FinanzasCuenta;
use Illuminate\Validation\Rules\Exists;

class DistribuirBolsillosRequest extends TenantFormRequest
{
    protected function prepareForValidation(): void
    {
        // Las filas vacías del formulario dinámico no cuentan como destinos.
        $destinos = collect((array) $this->input('destinos', []))
            ->filter(fn ($destino): bool => is_array($destino) && $this->filaConDatos($destino))
            ->values()
            ->all();

        $this->merge(['destinos' => $destinos]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'finanzas_subcuenta_id' => ['required', 'integer', $this->bolsilloActivoDeLaCuenta()],
            'fecha' => ['required', 'date'],
            'destinos' => ['required', 'array', 'min:1'],
            'destinos.*.finanzas_subcuenta_destino_id' => ['required', 'integer', $this->bolsilloActivoDeLaCuenta()],
            'destinos.*.monto' => ['required', 'numeric', 'min:0.01'],
        ];
    }

    public function toDto(): DistribucionData
    {
        $destinos = [];

        foreach ((array) $this->validated('destinos', []) as $destino) {
            $destinos[] = [
                'subcuenta_id' => (int) $destino['finanzas_subcuenta_destino_id'],
                'monto' => (float) $destino['monto'],
            ];
        }

        return new DistribucionData(
            origenSubcuentaId: $this->integer('finanzas_subcuenta_id'),
            fecha: (string) $this->validated('fecha'),
            destinos: $destinos,
        );
    }

    /**
     * @param  array<string, mixed>  $destino
     */
    private function filaConDatos(array $destino): bool
    {
        return filled($destino['finanzas_subcuenta_destino_id'] ?? null) || filled($destino['monto'] ?? null);
    }

    private function bolsilloActivoDeLaCuenta(): Exists
    {
        $cuenta = $this->route('cuenta');

        return $this->existeEnEmpresa('finanzas_subcuentas')
            ->where('finanzas_cuenta_id', $cuenta instanceof FinanzasCuenta ? $cuenta->id : 0)
            ->where('active', true);
    }
}

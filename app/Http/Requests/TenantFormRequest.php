<?php

namespace App\Http\Requests;

use App\Tenancy\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rules\Unique;

/**
 * Base de los Form Requests de módulos multiempresa.
 *
 * Las reglas exists/unique consultan la base sin pasar por Eloquent, así que
 * no heredan el scope de tenant: estos helpers les agregan el xnova_id.
 * La autorización por permiso la resuelve el middleware de la ruta.
 */
abstract class TenantFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function tenantId(): int
    {
        return app(TenantContext::class)->idOrFail();
    }

    protected function existeEnEmpresa(string $tabla, string $columna = 'id'): Exists
    {
        return Rule::exists($tabla, $columna)->where('xnova_id', $this->tenantId());
    }

    protected function unicoEnEmpresa(string $tabla, string $columna): Unique
    {
        return Rule::unique($tabla, $columna)->where('xnova_id', $this->tenantId());
    }

    protected function enteroOpcional(string $clave): ?int
    {
        return $this->filled($clave) ? $this->integer($clave) : null;
    }
}

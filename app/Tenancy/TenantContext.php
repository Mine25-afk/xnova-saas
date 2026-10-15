<?php

namespace App\Tenancy;

use App\Models\User;
use App\Tenancy\Exceptions\TenantNotResolvedException;
use Closure;
use Illuminate\Auth\AuthManager;

/**
 * Empresa (xnova) activa durante el request, job o comando en curso.
 *
 * El middleware ResolveTenant la fija a partir del usuario autenticado. Si algo
 * consulta antes de que corra el middleware (por ejemplo el route model binding)
 * se usa la empresa del propio usuario ya autenticado. Nunca se elige otra
 * empresa por descarte: sin tenant, el scope global no devuelve filas.
 */
final class TenantContext
{
    private ?int $tenantId = null;

    public function __construct(private readonly AuthManager $auth) {}

    public function set(int $tenantId): void
    {
        $this->tenantId = $tenantId;
    }

    public function clear(): void
    {
        $this->tenantId = null;
    }

    public function id(): ?int
    {
        if ($this->tenantId !== null) {
            return $this->tenantId;
        }

        return $this->tenantFromAuthenticatedUser();
    }

    public function hasTenant(): bool
    {
        return $this->id() !== null;
    }

    public function idOrFail(): int
    {
        $tenantId = $this->id();

        if ($tenantId === null) {
            throw new TenantNotResolvedException;
        }

        return $tenantId;
    }

    /**
     * Ejecuta el callback dentro de otra empresa y restaura la anterior al terminar.
     *
     * @template TResult
     *
     * @param  Closure(): TResult  $callback
     * @return TResult
     */
    public function runAs(int $tenantId, Closure $callback): mixed
    {
        $previous = $this->tenantId;
        $this->tenantId = $tenantId;

        try {
            return $callback();
        } finally {
            $this->tenantId = $previous;
        }
    }

    private function tenantFromAuthenticatedUser(): ?int
    {
        // Solo se consulta un guard que ya fue resuelto, para no disparar la autenticación desde un scope.
        if (! $this->auth->hasResolvedGuards()) {
            return null;
        }

        $user = $this->auth->guard()->user();

        if (! $user instanceof User || blank($user->xnova_id)) {
            return null;
        }

        return (int) $user->xnova_id;
    }
}

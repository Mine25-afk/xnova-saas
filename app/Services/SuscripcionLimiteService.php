<?php

namespace App\Services;

use App\Models\Sucursal;
use App\Models\Suscripcion;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class SuscripcionLimiteService
{
    protected const DEFAULT_USERS_LIMIT = 3;

    protected const DEFAULT_BRANCHES_LIMIT = 1;

    protected const TRIAL_CLIENTS_LIMIT = 10;

    protected const TRIAL_COLLABORATORS_LIMIT = 3;

    protected const TRIAL_POSITIONS_LIMIT = 3;

    protected const TRIAL_ROLES_LIMIT = 3;

    protected const TRIAL_APPOINTMENTS_LIMIT = 20;

    /**
     * @var array<int, string>
     */
    protected array $preferredStatuses = [
        'active',
        'approved',
        'approval_pending',
        'pending',
        'trialing',
        'trial',
        'pendiente',
        'paypal_event',
    ];

    public function asegurarCupoUsuariosDisponible(int $xnovaId): void
    {
        $actuales = User::query()
            ->where('xnova_id', $xnovaId)
            ->count();

        $limite = $this->resolverLimite(
            $xnovaId,
            'usuarios_totales',
            $actuales,
            self::DEFAULT_USERS_LIMIT,
        );

        if ($actuales >= $limite) {
            throw ValidationException::withMessages([
                'limite' => 'Solo tienes permiso para los usuarios configurados en tu suscripcion actual.',
            ]);
        }
    }

    public function asegurarCupoSucursalesDisponible(int $xnovaId): void
    {
        $actuales = Sucursal::query()
            ->where('xnova_id', $xnovaId)
            ->count();

        $limite = $this->resolverLimite(
            $xnovaId,
            'sucursales_totales',
            $actuales,
            self::DEFAULT_BRANCHES_LIMIT,
        );

        if ($actuales >= $limite) {
            throw ValidationException::withMessages([
                'limite' => 'Solo tienes permiso para las sucursales configuradas en tu suscripcion actual.',
            ]);
        }
    }

    public function asegurarCupoPruebaClientesDisponible(int $xnovaId): void
    {
        $this->asegurarLimitePruebaGratis(
            $xnovaId,
            \App\Models\SocioNegocio::query()->where('xnova_id', $xnovaId)->where('tipo', 'C')->count(),
            self::TRIAL_CLIENTS_LIMIT,
            'Solo puedes registrar 10 clientes durante la prueba gratis.',
        );
    }

    public function asegurarCupoPruebaColaboradoresDisponible(int $xnovaId): void
    {
        $this->asegurarLimitePruebaGratis(
            $xnovaId,
            \App\Models\SocioNegocio::query()->where('xnova_id', $xnovaId)->where('tipo', 'E')->count(),
            self::TRIAL_COLLABORATORS_LIMIT,
            'Solo puedes registrar 3 colaboradores durante la prueba gratis.',
        );
    }

    public function asegurarCupoPruebaPuestosDisponible(int $xnovaId): void
    {
        $this->asegurarLimitePruebaGratis(
            $xnovaId,
            \App\Models\Puesto::query()->where('xnova_id', $xnovaId)->count(),
            self::TRIAL_POSITIONS_LIMIT,
            'Solo puedes registrar 3 puestos durante la prueba gratis.',
        );
    }

    public function asegurarCupoPruebaRolesDisponible(int $xnovaId): void
    {
        $this->asegurarLimitePruebaGratis(
            $xnovaId,
            \App\Models\Rol::query()->where('xnova_id', $xnovaId)->count(),
            self::TRIAL_ROLES_LIMIT,
            'Solo puedes registrar 3 roles durante la prueba gratis.',
        );
    }

    public function asegurarCupoPruebaCitasDisponible(int $xnovaId): void
    {
        $this->asegurarLimitePruebaGratis(
            $xnovaId,
            \App\Models\Cita::query()->where('xnova_id', $xnovaId)->count(),
            self::TRIAL_APPOINTMENTS_LIMIT,
            'Solo puedes registrar 20 citas durante la prueba gratis.',
        );
    }

    protected function resolverLimite(
        int $xnovaId,
        string $column,
        int $currentCount,
        int $defaultLimit
    ): int {
        $suscripcion = $this->obtenerSuscripcionActual($xnovaId);
        $configuredLimit = (int) ($suscripcion?->{$column} ?? 0);

        if ($configuredLimit > 0) {
            return $configuredLimit;
        }

        return max($defaultLimit, $currentCount);
    }

    protected function asegurarLimitePruebaGratis(
        int $xnovaId,
        int $currentCount,
        int $limit,
        string $message
    ): void {
        if (! $this->esPruebaGratis($xnovaId)) {
            return;
        }

        if ($currentCount >= $limit) {
            throw ValidationException::withMessages([
                'limite' => $message,
            ]);
        }
    }

    protected function esPruebaGratis(int $xnovaId): bool
    {
        return ($this->obtenerSuscripcionActual($xnovaId)?->proveedor ?? null) === 'trial';
    }

    protected function obtenerSuscripcionActual(int $xnovaId): ?Suscripcion
    {
        $statuses = implode("','", $this->preferredStatuses);

        return Suscripcion::query()
            ->select('suscripciones.*')
            ->join('xnova_paquete', 'xnova_paquete.id', '=', 'suscripciones.xnova_paquete_id')
            ->where('xnova_paquete.xnova_id', $xnovaId)
            ->orderByRaw("CASE WHEN suscripciones.estado IN ('{$statuses}') THEN 1 ELSE 0 END DESC")
            ->orderByDesc('suscripciones.created_at')
            ->first();
    }
}

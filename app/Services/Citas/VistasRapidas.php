<?php

namespace App\Services\Citas;

use App\Models\Cita;
use Illuminate\Database\Eloquent\Builder;

/**
 * Vistas rápidas del listado de citas (todas, por autorizar y de hoy).
 */
final class VistasRapidas
{
    public const TODAS = 'todas';

    public const POR_AUTORIZAR = 'por-autorizar';

    public const HOY = 'hoy';

    /**
     * @return array<string, array{title: string, description: string, badge: string, locked_estado_id: int, only_today: bool}>
     */
    public function definiciones(): array
    {
        return [
            self::TODAS => [
                'title' => 'Todas las citas',
                'description' => 'Consulta toda la agenda y aplica filtros manuales segun la operacion que necesites.',
                'badge' => 'Vista general',
                'locked_estado_id' => 0,
                'only_today' => false,
            ],
            self::POR_AUTORIZAR => [
                'title' => 'Por autorizar',
                'description' => 'Muestra las reservaciones pendientes para revisarlas y confirmarlas mas rapido.',
                'badge' => 'Pendientes',
                'locked_estado_id' => Cita::ESTADO_RESERVADA_ID,
                'only_today' => false,
            ],
            self::HOY => [
                'title' => 'Citas de hoy',
                'description' => 'Agrupa la agenda del dia para que el equipo vea lo que toca atender ahora.',
                'badge' => 'Hoy',
                'locked_estado_id' => 0,
                'only_today' => true,
            ],
        ];
    }

    public function resolver(?string $vista): string
    {
        return array_key_exists((string) $vista, $this->definiciones()) ? (string) $vista : self::TODAS;
    }

    /**
     * Algunas vistas fijan el estado (p. ej. "por autorizar" solo muestra reservadas).
     */
    public function estadoEfectivo(int $estadoId, string $vista): int
    {
        $bloqueado = $this->definiciones()[$this->resolver($vista)]['locked_estado_id'];

        return $bloqueado > 0 ? $bloqueado : $estadoId;
    }

    /**
     * @param  Builder<Cita>  $query
     * @return Builder<Cita>
     */
    public function aplicar(Builder $query, string $vista): Builder
    {
        $config = $this->definiciones()[$this->resolver($vista)];

        if ($config['locked_estado_id'] > 0) {
            $query->where('estado_id', $config['locked_estado_id']);
        }

        if ($config['only_today']) {
            $query->whereDate('fecha', now()->toDateString());
        }

        return $query;
    }

    /**
     * @param  Builder<Cita>  $query
     * @return Builder<Cita>
     */
    public function ordenar(Builder $query, string $vista): Builder
    {
        if (in_array($vista, [self::POR_AUTORIZAR, self::HOY], true)) {
            return $query->orderBy('fecha')->orderBy('hora_inicio');
        }

        return $query->orderByDesc('fecha')->orderByDesc('hora_inicio');
    }
}

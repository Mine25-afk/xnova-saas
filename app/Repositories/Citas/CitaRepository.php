<?php

namespace App\Repositories\Citas;

use App\Models\Cita;
use App\Repositories\BaseRepository;
use App\Services\Citas\VistasRapidas;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends BaseRepository<Cita>
 */
class CitaRepository extends BaseRepository
{
    public const RELACIONES = [
        'cliente:dni,nombres,apellidos',
        'colaborador:dni,sucursal_Id',
        'colaborador.socio:dni,nombres,apellidos',
        'sucursal:id,nombre',
        'producto:id,nombre,descripcion,precio',
        'estado:id,nombre',
    ];

    public function __construct(private readonly VistasRapidas $vistas) {}

    protected function model(): string
    {
        return Cita::class;
    }

    /**
     * @return Builder<Cita>
     */
    public function buscar(string $search = '', int $estadoId = 0, string $vista = VistasRapidas::TODAS): Builder
    {
        $query = $this->query()
            ->select([
                'id',
                'cliente_dni',
                'colaborador_dni',
                'sucursal_Id',
                'producto_id',
                'fecha',
                'hora_inicio',
                'hora_salida',
                'estado_id',
                'descripcion',
                'comentario',
                'cliente_nombre_publico',
                'cliente_identidad_publica',
                'created_at',
            ])
            ->with(self::RELACIONES)
            ->when($estadoId > 0, fn (Builder $q) => $q->where('estado_id', $estadoId))
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $q) use ($search): void {
                    $q->where('descripcion', 'like', "%{$search}%")
                        ->orWhere('comentario', 'like', "%{$search}%")
                        ->orWhere('cliente_nombre_publico', 'like', "%{$search}%")
                        ->orWhere('cliente_identidad_publica', 'like', "%{$search}%")
                        ->orWhereHas('cliente', fn (Builder $c) => $c->where('nombres', 'like', "%{$search}%")->orWhere('apellidos', 'like', "%{$search}%"))
                        ->orWhereHas('colaborador.socio', fn (Builder $c) => $c->where('nombres', 'like', "%{$search}%")->orWhere('apellidos', 'like', "%{$search}%"))
                        ->orWhereHas('producto', fn (Builder $p) => $p->where('nombre', 'like', "%{$search}%"))
                        ->orWhereHas('sucursal', fn (Builder $s) => $s->where('nombre', 'like', "%{$search}%"));
                });
            });

        return $this->vistas->ordenar($this->vistas->aplicar($query, $vista), $vista);
    }

    public function contarVista(string $vista): int
    {
        return $this->vistas->aplicar($this->query(), $vista)->count();
    }

    public function contar(): int
    {
        return $this->query()->count();
    }

    /**
     * Primera cita activa del colaborador que se cruza con el rango dado.
     */
    public function conflictoDeColaborador(string $colaboradorDni, string $fecha, string $horaInicio, string $horaSalida, ?int $excluirCitaId = null): ?Cita
    {
        return $this->query()
            ->select(['id', 'fecha', 'hora_inicio', 'hora_salida'])
            ->where('colaborador_dni', $colaboradorDni)
            ->whereDate('fecha', $fecha)
            ->whereIn('estado_id', Cita::ESTADOS_ACTIVOS)
            ->when($excluirCitaId !== null, fn (Builder $q) => $q->whereKeyNot($excluirCitaId))
            ->where('hora_inicio', '<', $horaSalida)
            ->where('hora_salida', '>', $horaInicio)
            ->orderBy('hora_inicio')
            ->first();
    }

    /**
     * @return Collection<int, Cita>
     */
    public function activasDelColaborador(string $colaboradorDni, string $fecha): Collection
    {
        return $this->query()
            ->select(['hora_inicio', 'hora_salida'])
            ->where('colaborador_dni', $colaboradorDni)
            ->whereDate('fecha', $fecha)
            ->whereIn('estado_id', Cita::ESTADOS_ACTIVOS)
            ->orderBy('hora_inicio')
            ->get();
    }

    /**
     * @return Collection<int, Cita>
     */
    public function entreFechas(string $desde, string $hasta): Collection
    {
        return $this->query()
            ->with(['cliente:dni,nombres,apellidos', 'estado:id,nombre'])
            ->whereDate('fecha', '>=', $desde)
            ->whereDate('fecha', '<=', $hasta)
            ->orderBy('fecha')
            ->orderBy('hora_inicio')
            ->get();
    }
}

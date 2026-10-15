<?php

namespace App\Services\Citas;

use App\DTOs\Citas\ReservaPublicaData;
use App\Exceptions\BusinessRuleException;
use App\Models\Cita;
use App\Models\Colaborador;
use App\Models\Producto;
use App\Models\SocioNegocio;
use App\Models\Sucursal;
use App\Models\Xnova;
use App\Repositories\Citas\CitaRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

/**
 * Reserva pública de citas. Corre dentro del tenant que resolvió el
 * middleware ResolvePublicTenant, así que todas las consultas ya están
 * acotadas a la empresa de la URL.
 */
final class ReservaPublicaService
{
    public function __construct(
        private readonly CitaRepository $citas,
        private readonly AgendaService $agenda,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function datosFormulario(Xnova $empresa): array
    {
        $colaboradores = $this->colaboradoresDisponibles();

        return [
            'empresa' => $empresa,
            'productos' => Producto::query()
                ->select(['id', 'nombre', 'descripcion', 'precio', 'imagen_path'])
                ->where('active', true)
                ->orderBy('nombre')
                ->get(),
            'sucursales' => Sucursal::query()->select(['id', 'nombre'])->orderBy('nombre')->get(),
            'colaboradores' => $colaboradores,
            'singleCollaborator' => $colaboradores->count() === 1 ? $colaboradores->first() : null,
        ];
    }

    /**
     * Crea la cita como "reservada" (pendiente de autorizar por el equipo).
     */
    public function reservar(Xnova $empresa, ReservaPublicaData $data): Cita
    {
        $colaborador = Colaborador::query()->select(['dni', 'sucursal_Id'])->find($data->colaboradorDni);

        if ($colaborador === null || (int) ($colaborador->sucursal_Id ?? 0) <= 0) {
            throw BusinessRuleException::enCampo('colaborador_dni', 'El colaborador seleccionado no tiene sucursal asignada.');
        }

        $sucursalId = (int) $colaborador->sucursal_Id;
        $producto = Producto::query()->findOrFail($data->productoId);

        $cliente = $data->esInvitado()
            ? $this->clienteInvitado((int) $empresa->id)
            : $this->clienteGoogle((int) $empresa->id, (string) $data->googleEmail, (string) $data->googleNombre);

        $this->agenda->asegurarSinConflicto($data->colaboradorDni, $data->fecha, $data->horaInicio, $data->horaSalida);

        $mensajeHorario = $this->agenda
            ->evaluarHorario($sucursalId, $data->fecha, $data->horaInicio, $data->horaSalida)
            ->mensajePublico();

        if ($mensajeHorario !== null) {
            throw BusinessRuleException::enCampos(['hora_inicio', 'hora_salida'], $mensajeHorario);
        }

        $nombreCliente = trim(($cliente->nombres ?? '').' '.($cliente->apellidos ?? ''));

        return $this->citas->create([
            'cliente_dni' => $cliente->dni,
            'colaborador_dni' => $data->colaboradorDni,
            'sucursal_Id' => $sucursalId,
            'producto_id' => $producto->id,
            'fecha' => $data->fecha,
            'hora_inicio' => $data->horaInicio,
            'hora_salida' => $data->horaSalida,
            'estado_id' => Cita::ESTADO_RESERVADA_ID,
            'descripcion' => $producto->nombre,
            'comentario' => $data->comentario,
            'cliente_nombre_publico' => $nombreCliente !== '' ? $nombreCliente : 'Cliente',
            'cliente_identidad_publica' => $cliente->dni,
        ]);
    }

    /**
     * @return Collection<int, Colaborador>
     */
    private function colaboradoresDisponibles(): Collection
    {
        return Colaborador::query()
            ->with(['socio:dni,nombres,apellidos,active', 'sucursal:id,nombre'])
            ->whereHas('socio', fn (Builder $q) => $q->where('tipo', SocioNegocio::TIPO_COLABORADOR)->where('active', true))
            ->whereNotNull('sucursal_Id')
            ->orderBy('dni')
            ->get();
    }

    /**
     * Invitado: se usa el cliente por defecto de la empresa (o se crea uno genérico).
     */
    private function clienteInvitado(int $xnovaId): SocioNegocio
    {
        $clientes = SocioNegocio::query()
            ->where('tipo', SocioNegocio::TIPO_CLIENTE)
            ->where('active', true);

        $existente = (clone $clientes)->where('es_por_defecto', true)->first()
            ?? (clone $clientes)->orderBy('created_at')->first();

        if ($existente !== null) {
            return $existente;
        }

        return SocioNegocio::query()->create([
            'dni' => $this->generarDni($xnovaId, 'GENCLI'),
            'nombres' => 'Cliente',
            'apellidos' => 'General',
            'fecha_nacimiento' => now()->subYears(18)->toDateString(),
            'telefono' => '0000-0000',
            'correo' => 'cliente.general.'.$xnovaId.'@xnova.local',
            'sexo' => 'M',
            'tipo' => SocioNegocio::TIPO_CLIENTE,
            'active' => true,
            'es_por_defecto' => true,
        ]);
    }

    private function clienteGoogle(int $xnovaId, string $email, string $nombre): SocioNegocio
    {
        $correo = Str::lower(trim($email));

        $existente = SocioNegocio::query()
            ->where('tipo', SocioNegocio::TIPO_CLIENTE)
            ->whereRaw('LOWER(correo) = ?', [$correo])
            ->first();

        if ($existente !== null) {
            return $existente;
        }

        $partes = array_values(array_filter(explode(' ', trim((string) preg_replace('/\s+/', ' ', $nombre)))));

        if (count($partes) <= 1) {
            $nombres = $partes[0] ?? 'Cliente';
            $apellidos = 'Google';
        } else {
            $apellidos = (string) array_pop($partes);
            $nombres = implode(' ', $partes);
        }

        return SocioNegocio::query()->create([
            'dni' => $this->generarDni($xnovaId, 'CLIWEB'),
            'nombres' => $nombres,
            'apellidos' => $apellidos,
            'fecha_nacimiento' => now()->subYears(18)->toDateString(),
            'telefono' => '0000-0000',
            'correo' => $correo,
            'sexo' => 'M',
            'tipo' => SocioNegocio::TIPO_CLIENTE,
            'active' => true,
            'es_por_defecto' => false,
        ]);
    }

    /**
     * El dni es llave primaria global, así que la unicidad se verifica en todas las empresas.
     */
    private function generarDni(int $xnovaId, string $prefijo): string
    {
        do {
            $dni = Str::upper($prefijo.$xnovaId.Str::random(6));
        } while (SocioNegocio::query()->acrossTenants()->whereKey($dni)->exists());

        return $dni;
    }
}

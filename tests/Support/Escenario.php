<?php

namespace Tests\Support;

use App\Models\Colaborador;
use App\Models\Horario;
use App\Models\HorarioDetalle;
use App\Models\Modulo;
use App\Models\Pantalla;
use App\Models\Permiso;
use App\Models\Producto;
use App\Models\Puesto;
use App\Models\Rol;
use App\Models\SocioNegocio;
use App\Models\Sucursal;
use App\Models\User;
use App\Models\Xnova;
use Illuminate\Support\Facades\DB;

/**
 * Arma una empresa completa para pruebas: dueño con rol y permisos, sucursal
 * con horario (L-V 08-17 con receso 12-13, sábado 08-12, domingo cerrado),
 * colaborador, cliente y producto.
 *
 * Importante: crear todos los escenarios ANTES de actingAs(), porque después
 * el TenantContext ya apunta a la empresa del usuario autenticado.
 */
final class Escenario
{
    public const PERMISOS = [
        'citas.ver', 'citas.crear', 'citas.editar', 'citas.detalles', 'citas.terminar', 'citas.cancelar',
        'citas.calendario.ver',
        'finanzas.dashboard.ver', 'finanzas.cuentas.ver', 'finanzas.cuentas.crear', 'finanzas.cuentas.detalles',
        'finanzas.cuentas.editar', 'finanzas.movimientos.ver', 'finanzas.movimientos.crear', 'finanzas.movimientos.editar',
        'finanzas.movimientos.detalles', 'finanzas.deudas.ver', 'finanzas.deudas.crear', 'finanzas.deudas.pagar',
        'finanzas.deudas.detalles', 'finanzas.cuentas-por-cobrar.cobrar', 'finanzas.reportes.ver',
        'finanzas.bolsillos.crear', 'finanzas.bolsillos.distribuir', 'finanzas.propietarios.crear',
        'seguridad.usuarios.detalles', 'seguridad.usuarios.editar',
    ];

    public Xnova $xnova;

    public User $user;

    public Rol $rol;

    public Sucursal $sucursal;

    public SocioNegocio $colaborador;

    public SocioNegocio $cliente;

    public Producto $producto;

    /**
     * @param  list<string>|null  $permisos
     */
    public static function crear(?array $permisos = null): self
    {
        self::asegurarEstados();

        $escenario = new self;
        $escenario->user = User::factory()->create();
        $escenario->xnova = Xnova::factory()->create(['user_id' => $escenario->user->id]);
        $xnovaId = $escenario->xnova->id;

        $escenario->rol = Rol::factory()->create(['xnova_id' => $xnovaId]);
        $escenario->rol->permisos()->sync(self::permisos($permisos ?? self::PERMISOS));

        $horario = Horario::query()->create(['nombre' => 'Horario base', 'active' => true, 'xnova_id' => $xnovaId]);

        foreach (range(0, 6) as $dia) {
            HorarioDetalle::query()->create([
                'horario_id' => $horario->id,
                'nombre' => 'Dia '.$dia,
                'dia' => $dia,
                'hora_inicio' => $dia === 0 ? null : '08:00',
                'hora_salida' => $dia === 0 ? null : ($dia === 6 ? '12:00' : '17:00'),
                'receso_incio' => in_array($dia, [0, 6], true) ? null : '12:00',
                'receso_salida' => in_array($dia, [0, 6], true) ? null : '13:00',
            ]);
        }

        $escenario->sucursal = Sucursal::factory()->create(['xnova_id' => $xnovaId, 'horario_id' => $horario->id]);
        $puesto = Puesto::factory()->create(['xnova_id' => $xnovaId]);

        $escenario->colaborador = SocioNegocio::factory()->colaborador()->porDefecto()->create(['xnova_id' => $xnovaId]);
        Colaborador::factory()->create([
            'dni' => $escenario->colaborador->dni,
            'puesto_id' => $puesto->id,
            'sucursal_Id' => $escenario->sucursal->id,
            'xnova_id' => $xnovaId,
        ]);

        $escenario->cliente = SocioNegocio::factory()->porDefecto()->create(['xnova_id' => $xnovaId]);
        $escenario->producto = Producto::factory()->create(['xnova_id' => $xnovaId]);

        $escenario->user->update([
            'xnova_id' => $xnovaId,
            'rol_id' => $escenario->rol->id,
            'sucursal_Id' => $escenario->sucursal->id,
        ]);

        return $escenario;
    }

    /**
     * @param  list<string>  $codigos
     * @return list<int>
     */
    private static function permisos(array $codigos): array
    {
        $modulo = Modulo::query()->firstOrCreate(['codigo' => 'PRUEBAS'], ['nombre' => 'Pruebas']);
        $pantalla = Pantalla::query()->firstOrCreate(
            ['codigo' => 'PRUEBAS'],
            ['nombre' => 'Pruebas', 'modulo_id' => $modulo->id, 'ruta' => 'dashboard'],
        );

        $ids = [];

        foreach ($codigos as $codigo) {
            $ids[] = (int) Permiso::query()->firstOrCreate(
                ['codigo' => $codigo],
                ['nombre' => $codigo, 'modulo_id' => $modulo->id, 'pantalla_id' => $pantalla->id],
            )->id;
        }

        return $ids;
    }

    private static function asegurarEstados(): void
    {
        if (DB::table('estados')->exists()) {
            return;
        }

        DB::table('estados')->insert([
            ['id' => 1, 'nombre' => 'Reservada'],
            ['id' => 2, 'nombre' => 'Terminada'],
            ['id' => 3, 'nombre' => 'Cancelada'],
            ['id' => 4, 'nombre' => 'Autorizado'],
        ]);
    }
}

<?php

namespace App\Services;

use App\Models\Colaborador;
use App\Models\Horario;
use App\Models\HorarioDetalle;
use App\Models\Paquete;
use App\Models\Permiso;
use App\Models\Puesto;
use App\Models\Rol;
use App\Models\SocioNegocio;
use App\Models\Sucursal;
use App\Models\Suscripcion;
use App\Models\User;
use App\Models\Xnova;
use App\Models\XnovaPaquete;
use Carbon\Carbon;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OnboardingProvisioningService
{
    public function complete(array $googleOnboarding, Paquete $package, array $checkout, array $formData): array
    {
        return DB::transaction(function () use ($googleOnboarding, $package, $checkout, $formData) {
            // 1. Tomamos los datos principales que vienen de Google.
            $googleEmail = $googleOnboarding['email'] ?? null;
            $googleId = $googleOnboarding['google_id'] ?? null;

            // 2. Buscamos si el usuario ya existe por Google o por correo.
            $user = null;
     
            $user = User::query()->create([
                'name' => $formData['user_name'],
                'email' => $googleEmail,
                'password' => null,
                'google_id' => $googleId,
                'email_verified_at' => now(),
            ]);

            $created = true;
            

            // 5. Creamos la empresa del usuario.
            $xnova = Xnova::query()->create([
                'nombre' => $formData['company_name'],
                'xnova_tipo_id' => $checkout['type_id'] ?? null,
                'active' => true,
                'user_id' => $user->id,
            ]);

            // 6. Creamos el rol general y le damos todos los permisos.
            $generalRole = Rol::query()->firstOrCreate([
                'nombre' => 'General',
                'xnova_id' => $xnova->id,
            ]);

            $generalRole->permisos()->sync($this->allowedPermissionIds(
                (int) ($checkout['type_id'] ?? 0),
                (string) ($checkout['type_variant'] ?? 'citas')
            ));

            $requiresDefaultEntities = (bool) ($checkout['requires_default_entities'] ?? (($checkout['type_variant'] ?? 'citas') !== 'catalogos'));
            $generalPuesto = null;
            $branch = null;
            $employee = null;
            $client = null;

            if ($requiresDefaultEntities) {
                // 7. Creamos el puesto general.
                $generalPuesto = Puesto::query()->create(
                    [
                        'nombre' => 'General',
                        'xnova_id' => $xnova->id,
                        'active' => true,
                    ],
                );

                // 8. Creamos la sucursal base.
                $branch = Sucursal::query()->create([
                    'nombre' => $formData['branch_name'],
                    'horario_id' => 0,
                    'xnova_id' => $xnova->id,
                ]);

                $this->assignDefaultBranchSchedule($branch, $xnova->id);

                // 9. Separamos el nombre del empleado por defecto.
                $cleanEmployeeName = trim((string) preg_replace('/\s+/', ' ', $formData['default_employee_name']));
                $employeeParts = array_values(array_filter(explode(' ', $cleanEmployeeName)));

                if (count($employeeParts) <= 1) {
                    $employeeNames = $employeeParts[0] ?? 'General';
                    $employeeLastNames = 'Base';
                } else {
                    $employeeLastNames = array_pop($employeeParts);
                    $employeeNames = implode(' ', $employeeParts);
                }

                $employeeCode = Str::upper(sprintf('EMP%03d%s', $xnova->id, now()->format('His')));

                // 10. Creamos el empleado base.
                $employee = SocioNegocio::query()->create([
                    'dni' => $employeeCode,
                    'nombres' => $employeeNames,
                    'apellidos' => $employeeLastNames,
                    'fecha_nacimiento' => now()->subYears(20)->toDateString(),
                    'telefono' => '0000-0000',
                    'sexo' => 'M',
                    'tipo' => 'E',
                    'xnova_id' => $xnova->id,
                    'active' => true,
                    'es_por_defecto' => true,
                ]);

                // 11. Lo enlazamos como colaborador dentro de la sucursal base.
                Colaborador::query()->create([
                    'dni' => $employee->dni,
                    'puesto_id' => $generalPuesto->id,
                    'sucursal_Id' => $branch->id,
                    'xnova_id' => $xnova->id,
                ]);

                // 12. Separamos el nombre del cliente por defecto.
                $cleanClientName = trim((string) preg_replace('/\s+/', ' ', $formData['default_client_name']));
                $clientParts = array_values(array_filter(explode(' ', $cleanClientName)));

                if (count($clientParts) <= 1) {
                    $clientNames = $clientParts[0] ?? 'General';
                    $clientLastNames = 'Base';
                } else {
                    $clientLastNames = array_pop($clientParts);
                    $clientNames = implode(' ', $clientParts);
                }

                $clientCode = Str::upper(sprintf('CLI%03d%s', $xnova->id, now()->format('His')));

                // 13. Creamos el cliente base.
                $client = SocioNegocio::query()->create([
                    'dni' => $clientCode,
                    'nombres' => $clientNames,
                    'apellidos' => $clientLastNames,
                    'fecha_nacimiento' => now()->subYears(20)->toDateString(),
                    'telefono' => '0000-0000',
                    'sexo' => 'M',
                    'tipo' => 'C',
                    'xnova_id' => $xnova->id,
                    'active' => true,
                    'es_por_defecto' => true,
                ]);
            }

            // 14. Terminamos de ligar el usuario con la empresa, rol y sucursal.
            $user->xnova_id = $xnova->id;
            $user->rol_id = $generalRole->id;
            $user->sucursal_Id = $branch?->id;
            $user->colaborador_dni = $employee?->dni;
            $user->save();

            // 15. Convertimos fechas del checkout. Si algo viene vacio o mal, usamos valores seguros.
            try {
                $periodStart = blank($checkout['period_start'] ?? null)
                    ? now()
                    : Carbon::parse($checkout['period_start']);
            } catch (\Throwable) {
                $periodStart = now();
            }

            try {
                $periodEnd = blank($checkout['period_end'] ?? null)
                    ? null
                    : Carbon::parse($checkout['period_end']);
            } catch (\Throwable) {
                $periodEnd = null;
            }

            // 16. Guardamos el paquete activo dentro de la empresa.
            $xnovaPaquete = XnovaPaquete::query()->create([
                'xnova_id' => $xnova->id,
                'paquete_id' => $package->id,
                'precio_mensual' => $checkout['monthly_total'],
                'fecha_inicio' => $periodStart->toDateString(),
                'fecha_fin' => ($checkout['provider'] ?? null) === 'trial' && $periodEnd
                    ? $periodEnd->toDateString()
                    : null,
                'activo' => true,
            ]);

            // 17. Guardamos el detalle de suscripcion para control interno.
            Suscripcion::query()->create([
                'xnova_paquete_id' => $xnovaPaquete->id,
                'proveedor' => $checkout['provider'],
                'estado' => $checkout['status'],
                'plan_externo' => $checkout['plan_id'] ?? null,
                'referencia_externa' => $checkout['reference'],
                'usuarios_incluidos' => $checkout['included_users'],
                'usuarios_extra' => $checkout['extra_users'],
                'usuarios_totales' => $checkout['selected_users'],
                'sucursales_incluidas' => $checkout['included_branches'],
                'sucursales_extra' => $checkout['extra_branches'],
                'sucursales_totales' => $checkout['selected_branches'],
                'precio_base' => $checkout['base_price'],
                'precio_usuario_extra' => $checkout['extra_user_price'],
                'precio_sucursal_extra' => $checkout['extra_branch_price'],
                'monto_total' => $checkout['monthly_total'],
                'moneda' => $checkout['currency'],
                'fecha_activacion' => $checkout['activated_at'],
                'periodo_inicio' => $checkout['period_start'],
                'periodo_fin' => $checkout['period_end'],
                'payload' => $checkout['payload'] ?? null,
            ]);

            // 18. Si el usuario es nuevo, disparamos el evento de registro.
            if ($created) {
                event(new Registered($user));
            }

            // 19. Regresamos todo lo necesario para continuar el login.
            return [
                'user' => $user,
                'company' => $xnova,
                'branch' => $branch,
                'role' => $generalRole,
                'client' => $client?->toArray(),
                'employee' => $employee?->toArray(),
            ];
        });
    }

    protected function allowedPermissionIds(int $typeId, string $typeVariant): array
    {
        $moduleIds = DB::table('xnova_tipo_modulo')
            ->where('xnova_tipo_id', $typeId)
            ->pluck('modulo_id');

        if ($moduleIds->isEmpty()) {
            $fallbackModuleCodes = match ($typeVariant) {
                'catalogos' => ['INVENTARIO'],
                'finanzas' => ['SEGURIDAD', 'FINANZAS'],
                default => ['SEGURIDAD', 'CONFIGURACION', 'SOCIOS', 'CITAS', 'INVENTARIO'],
            };

            $moduleIds = DB::table('modulos')
                ->whereIn('codigo', $fallbackModuleCodes)
                ->pluck('id');
        }

        $allowedCatalogScreenIds = DB::table('pantallas')
            ->whereIn('codigo', ['INVENTARIO_PRODUCTOS', 'INVENTARIO_RESERVACION'])
            ->pluck('id');

        return Permiso::query()
            ->whereIn('modulo_id', $moduleIds)
            ->when($typeVariant === 'catalogos', function ($query) use ($allowedCatalogScreenIds) {
                $query->where(function ($subQuery) use ($allowedCatalogScreenIds) {
                    $subQuery->whereNull('pantalla_id')
                        ->orWhereIn('pantalla_id', $allowedCatalogScreenIds);
                });
            })
            ->pluck('id')
            ->all();
    }

    protected function assignDefaultBranchSchedule(Sucursal $branch, int $xnovaId): void
    {
        $horario = Horario::query()->create([
            'nombre' => 'Horario '.trim((string) $branch->nombre),
            'active' => true,
            'xnova_id' => $xnovaId,
        ]);

        $defaultRows = [
            ['dia' => 0, 'nombre' => 'Domingo', 'hora_inicio' => null, 'hora_salida' => null, 'receso_incio' => null, 'receso_salida' => null],
            ['dia' => 1, 'nombre' => 'Lunes', 'hora_inicio' => '08:00', 'hora_salida' => '17:00', 'receso_incio' => '12:00', 'receso_salida' => '13:00'],
            ['dia' => 2, 'nombre' => 'Martes', 'hora_inicio' => '08:00', 'hora_salida' => '17:00', 'receso_incio' => '12:00', 'receso_salida' => '13:00'],
            ['dia' => 3, 'nombre' => 'Miercoles', 'hora_inicio' => '08:00', 'hora_salida' => '17:00', 'receso_incio' => '12:00', 'receso_salida' => '13:00'],
            ['dia' => 4, 'nombre' => 'Jueves', 'hora_inicio' => '08:00', 'hora_salida' => '17:00', 'receso_incio' => '12:00', 'receso_salida' => '13:00'],
            ['dia' => 5, 'nombre' => 'Viernes', 'hora_inicio' => '08:00', 'hora_salida' => '17:00', 'receso_incio' => '12:00', 'receso_salida' => '13:00'],
            ['dia' => 6, 'nombre' => 'Sabado', 'hora_inicio' => '08:00', 'hora_salida' => '12:00', 'receso_incio' => null, 'receso_salida' => null],
        ];

        foreach ($defaultRows as $row) {
            HorarioDetalle::query()->create([
                'nombre' => $row['nombre'],
                'dia' => $row['dia'],
                'hora_inicio' => $row['hora_inicio'],
                'hora_salida' => $row['hora_salida'],
                'receso_incio' => $row['receso_incio'],
                'receso_salida' => $row['receso_salida'],
                'horario_id' => $horario->id,
            ]);
        }

        $branch->update([
            'horario_id' => $horario->id,
        ]);
    }
}

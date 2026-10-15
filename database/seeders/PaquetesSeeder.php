<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PaquetesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();
        $defaultPaypalPlanId = config('services.paypal.plan_id');
        $defaultPaypalProductId = config('services.paypal.product_id');

        // Nota:
        // Con el esquema actual el paquete se relaciona con modulos.
        // Si luego quieres vender permisos individuales, lo ideal es crear
        // una tabla pivote `paquete_permiso` usando este mismo patron.
        $paquetes = [
            [
                'nombre' => 'Citas',
                'descripcion' => 'Paquete inicial para gestionar agenda, clientes, colaboradores y la configuracion base del negocio.',
                'precio' => 10.00,
                'usuarios_incluidos' => 3,
                'precio_usuario_extra' => 1.50,
                'sucursales_incluidas' => 1,
                'precio_sucursal_extra' => 3.00,
                'paypal_plan_id' => null,
                'moneda' => 'USD',
                'activo' => true,
                'modulos' => ['CONFIGURACION', 'SOCIOS', 'CITAS', 'INVENTARIO', 'SEGURIDAD'],
            ],
            [
                'nombre' => 'Finanzas en Pareja',
                'descripcion' => 'Paquete simple para organizar cuentas, ingresos, gastos fijos y movimientos personales o en pareja.',
                'precio' => 2.00,
                'usuarios_incluidos' => 2,
                'precio_usuario_extra' => 1.00,
                'sucursales_incluidas' => 0,
                'precio_sucursal_extra' => 0.00,
                'paypal_plan_id' => null,
                'moneda' => 'USD',
                'activo' => true,
                'modulos' => ['SEGURIDAD', 'FINANZAS'],
            ],
        ];

        foreach ($paquetes as $paqueteData) {
            $existingPackage = DB::table('paquetes')
                ->where('nombre', $paqueteData['nombre'])
                ->first();

            DB::table('paquetes')->updateOrInsert(
                ['nombre' => $paqueteData['nombre']],
                [
                    'descripcion' => $paqueteData['descripcion'],
                    'precio' => $paqueteData['precio'],
                    'usuarios_incluidos' => $paqueteData['usuarios_incluidos'],
                    'precio_usuario_extra' => $paqueteData['precio_usuario_extra'],
                    'sucursales_incluidas' => $paqueteData['sucursales_incluidas'],
                    'precio_sucursal_extra' => $paqueteData['precio_sucursal_extra'],
                    'paypal_plan_id' => $defaultPaypalPlanId ?: $existingPackage?->paypal_plan_id ?: $paqueteData['paypal_plan_id'],
                    'paypal_product_id' => $defaultPaypalProductId ?: $existingPackage?->paypal_product_id,
                    'moneda' => $paqueteData['moneda'],
                    'activo' => $paqueteData['activo'],
                    'updated_at' => $now,
                    'created_at' => $now,
                ],
            );

            $paqueteId = DB::table('paquetes')
                ->where('nombre', $paqueteData['nombre'])
                ->value('id');

            foreach ($paqueteData['modulos'] as $codigoModulo) {
                $moduloId = DB::table('modulos')
                    ->where('codigo', $codigoModulo)
                    ->value('id');

                if (! $moduloId) {
                    continue;
                }

                DB::table('paquete_modulo')->updateOrInsert(
                    [
                        'paquete_id' => $paqueteId,
                        'modulo_id' => $moduloId,
                    ],
                    [
                        'updated_at' => $now,
                        'created_at' => $now,
                    ],
                );
            }
        }
    }
}

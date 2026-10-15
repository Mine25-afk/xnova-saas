<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CatalogoAccesosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();
        $obsoleteScreenCodes = ['SEGURIDAD_USUARIOS'];
        $obsoleteButtonCodes = ['USUARIOS_CREAR', 'USUARIOS_EDITAR', 'USUARIOS_BLOQUEAR'];
        $obsoletePermissionCodes = [
            'seguridad.usuarios.ver',
            'seguridad.usuarios.crear',
            'seguridad.usuarios.editar',
            'seguridad.usuarios.bloquear',
            'seguridad.usuarios.detalles',
            'seguridad.usuarios.activar',
            'seguridad.usuarios.desactivar',
        ];

        $obsoleteScreenIds = DB::table('pantallas')
            ->whereIn('codigo', $obsoleteScreenCodes)
            ->pluck('id');

        $obsoleteButtonIds = DB::table('botones')
            ->whereIn('codigo', $obsoleteButtonCodes)
            ->orWhereIn('pantalla_id', $obsoleteScreenIds)
            ->pluck('id');

        DB::table('permisos')
            ->whereIn('codigo', $obsoletePermissionCodes)
            ->orWhereIn('boton_id', $obsoleteButtonIds)
            ->orWhereIn('pantalla_id', $obsoleteScreenIds)
            ->delete();

        DB::table('botones')
            ->whereIn('id', $obsoleteButtonIds)
            ->delete();

        DB::table('pantallas')
            ->whereIn('id', $obsoleteScreenIds)
            ->delete();

        $catalogo = [
            [
                'nombre' => 'Seguridad',
                'codigo' => 'SEGURIDAD',
                'pantallas' => [
                    [
                        'nombre' => 'Usuarios',
                        'codigo' => 'SEGURIDAD_USUARIOS',
                        'ruta' => 'usuarios.listado',
                        'permiso_ver' => [
                            'nombre' => 'Ver listado de usuarios',
                            'codigo' => 'seguridad.usuarios.ver',
                        ],
                        'botones' => [
                            [
                                'nombre' => 'Crear',
                                'codigo' => 'USUARIOS_CREAR',
                                'permiso_nombre' => 'Crear usuarios',
                                'permiso_codigo' => 'seguridad.usuarios.crear',
                            ],
                            [
                                'nombre' => 'Editar',
                                'codigo' => 'USUARIOS_EDITAR',
                                'permiso_nombre' => 'Editar usuarios',
                                'permiso_codigo' => 'seguridad.usuarios.editar',
                            ],
                            [
                                'nombre' => 'Detalles',
                                'codigo' => 'USUARIOS_DETALLES',
                                'permiso_nombre' => 'Ver detalles de usuarios',
                                'permiso_codigo' => 'seguridad.usuarios.detalles',
                            ],
                            [
                                'nombre' => 'Activar',
                                'codigo' => 'USUARIOS_ACTIVAR',
                                'permiso_nombre' => 'Activar usuarios',
                                'permiso_codigo' => 'seguridad.usuarios.activar',
                            ],
                            [
                                'nombre' => 'Desactivar',
                                'codigo' => 'USUARIOS_DESACTIVAR',
                                'permiso_nombre' => 'Desactivar usuarios',
                                'permiso_codigo' => 'seguridad.usuarios.desactivar',
                            ],
                        ],
                    ],
                    [
                        'nombre' => 'Roles',
                        'codigo' => 'SEGURIDAD_ROLES',
                        'ruta' => 'roles.listado',
                        'permiso_ver' => [
                            'nombre' => 'Ver listado de roles',
                            'codigo' => 'seguridad.roles.ver',
                        ],
                        'botones' => [
                            [
                                'nombre' => 'Crear',
                                'codigo' => 'ROLES_CREAR',
                                'permiso_nombre' => 'Crear roles',
                                'permiso_codigo' => 'seguridad.roles.crear',
                            ],
                            [
                                'nombre' => 'Editar',
                                'codigo' => 'ROLES_EDITAR',
                                'permiso_nombre' => 'Editar roles',
                                'permiso_codigo' => 'seguridad.roles.editar',
                            ],
                            [
                                'nombre' => 'Detalles',
                                'codigo' => 'ROLES_DETALLES',
                                'permiso_nombre' => 'Ver detalles de roles',
                                'permiso_codigo' => 'seguridad.roles.detalles',
                            ],
                            
                        ],
                    ],
                ],
            ],
            [
                'nombre' => 'Configuracion',
                'codigo' => 'CONFIGURACION',
                'pantallas' => [
                    [
                        'nombre' => 'Sucursales',
                        'codigo' => 'CONFIG_SUCURSALES',
                        'ruta' => 'sucursales.listado',
                        'permiso_ver' => [
                            'nombre' => 'Ver listado de sucursales',
                            'codigo' => 'configuracion.sucursales.ver',
                        ],
                        'botones' => [
                            [
                                'nombre' => 'Crear',
                                'codigo' => 'SUCURSALES_CREAR',
                                'permiso_nombre' => 'Crear sucursales',
                                'permiso_codigo' => 'configuracion.sucursales.crear',
                            ],
                            [
                                'nombre' => 'Editar',
                                'codigo' => 'SUCURSALES_EDITAR',
                                'permiso_nombre' => 'Editar sucursales',
                                'permiso_codigo' => 'configuracion.sucursales.editar',
                            ],
                            [
                                'nombre' => 'Detalles',
                                'codigo' => 'SUCURSALES_DETALLES',
                                'permiso_nombre' => 'Ver detalles de sucursales',
                                'permiso_codigo' => 'configuracion.sucursales.detalles',
                            ],
                        ],
                    ], [
                        'nombre' => 'Puestos',
                        'codigo' => 'CONFI_PUESTOS',
                        'ruta' => 'puestos.page',
                        'permiso_ver' => [
                            'nombre' => 'Ver listado de puestos',
                            'codigo' => 'configuracion.puestos.ver',
                        ],
                        'botones' => [
                            [
                                'nombre' => 'Crear',
                                'codigo' => 'PUESTOS_CREAR',
                                'permiso_nombre' => 'Crear puestos',
                                'permiso_codigo' => 'configuracion.puestos.crear',
                            ],
                            [
                                'nombre' => 'Editar',
                                'codigo' => 'PUESTOS_EDITAR',
                                'permiso_nombre' => 'Editar puestos',
                                'permiso_codigo' => 'configuracion.puestos.editar',
                            ],
                            [
                                'nombre' => 'Activar',
                                'codigo' => 'PUESTOS_ACTIVAR',
                                'permiso_nombre' => 'Activar puestos',
                                'permiso_codigo' => 'configuracion.puestos.activar',
                            ],
                            [
                                'nombre' => 'Desactivar',
                                'codigo' => 'PUESTOS_DESACTIVAR',
                                'permiso_nombre' => 'Desactivar puestos',
                                'permiso_codigo' => 'configuracion.puestos.desactivar',
                            ],
                        ],
                    ],
                ],
            ],
            [
                'nombre' => 'Socios de Negocio',
                'codigo' => 'SOCIOS',
                'pantallas' => [
                    [
                        'nombre' => 'Clientes',
                        'codigo' => 'SOCIOS_CLIENTES',
                        'ruta' => 'socios.clientes.listado',
                        'permiso_ver' => [
                            'nombre' => 'Ver listado de clientes',
                            'codigo' => 'socios.clientes.ver',
                        ],
                        'botones' => [
                            [
                                'nombre' => 'Crear',
                                'codigo' => 'CLIENTES_CREAR',
                                'permiso_nombre' => 'Crear clientes',
                                'permiso_codigo' => 'socios.clientes.crear',
                            ],
                            [
                                'nombre' => 'Editar',
                                'codigo' => 'CLIENTES_EDITAR',
                                'permiso_nombre' => 'Editar clientes',
                                'permiso_codigo' => 'socios.clientes.editar',
                            ],
                            [
                                'nombre' => 'Detalles',
                                'codigo' => 'CLIENTES_DETALLES',
                                'permiso_nombre' => 'Ver detalles de clientes',
                                'permiso_codigo' => 'socios.clientes.detalles',
                            ],
                        ],
                    ],
                    [
                        'nombre' => 'Colaboradores',
                        'codigo' => 'SOCIOS_COLABORADORES',
                        'ruta' => 'socios.colaboradores.listado',
                        'permiso_ver' => [
                            'nombre' => 'Ver listado de colaboradores',
                            'codigo' => 'socios.colaboradores.ver',
                        ],
                        'botones' => [
                            [
                                'nombre' => 'Crear',
                                'codigo' => 'COLABORADORES_CREAR',
                                'permiso_nombre' => 'Crear colaboradores',
                                'permiso_codigo' => 'socios.colaboradores.crear',
                            ],
                            [
                                'nombre' => 'Editar',
                                'codigo' => 'COLABORADORES_EDITAR',
                                'permiso_nombre' => 'Editar colaboradores',
                                'permiso_codigo' => 'socios.colaboradores.editar',
                            ],
                            [
                                'nombre' => 'Detalles',
                                'codigo' => 'COLABORADORES_DETALLES',
                                'permiso_nombre' => 'Ver detalles de colaboradores',
                                'permiso_codigo' => 'socios.colaboradores.detalles',
                            ],
                        ],
                    ],
                ],
            ],[
                'nombre' => 'Citas',
                'codigo' => 'CITAS',
                'pantallas' => [
                    [
                        'nombre' => 'Listado de Citas',
                        'codigo' => 'CITAS_LISTADO',
                        'ruta' => 'citas.listado',
                        'permiso_ver' => [
                            'nombre' => 'Ver listado de citas',
                            'codigo' => 'citas.ver',
                        ],
                        'botones' => [
                            [
                                'nombre' => 'Crear',
                                'codigo' => 'CITAS_CREAR',
                                'permiso_nombre' => 'Crear citas',
                                'permiso_codigo' => 'citas.crear',
                            ],
                            [
                                'nombre' => 'Editar',
                                'codigo' => 'CITAS_EDITAR',
                                'permiso_nombre' => 'Editar citas',
                                'permiso_codigo' => 'citas.editar',
                            ],
                            [
                                'nombre' => 'Detalles',
                                'codigo' => 'CITAS_DETALLES',
                                'permiso_nombre' => 'Ver detalles de citas',
                                'permiso_codigo' => 'citas.detalles',
                            ],
                            [
                                'nombre' => 'Terminar',
                                'codigo' => 'CITAS_TERMINAR',
                                'permiso_nombre' => 'Terminar citas',
                                'permiso_codigo' => 'citas.terminar',
                            ],
                             [
                                'nombre' => 'Cancelar',
                                'codigo' => 'CITAS_CANCELAR',
                                'permiso_nombre' => 'Cancelar citas',
                                'permiso_codigo' => 'citas.cancelar',
                            ],
                        ],
                    ], [
                        'nombre' => 'Calendario de Citas',
                        'codigo' => 'CITAS_CALENDARIO',
                        'ruta' => 'citas.calendario',
                        'permiso_ver' => [
                            'nombre' => 'Ver calendario de citas',
                            'codigo' => 'citas.calendario.ver',
                        ],  
                    ], 
                    [
                        'nombre' => 'Reporte de Citas',
                        'codigo' => 'CITAS_REPORTES',
                        'ruta' => 'citas.reportes',
                        'permiso_ver' => [
                            'nombre' => 'Ver reporte de citas',
                            'codigo' => 'citas.reportes.ver',
                        ],
                    ],
                ],
            ],
            [
                'nombre' => 'Inventario',
                'codigo' => 'INVENTARIO',
                'pantallas' => [
                    [
                        'nombre' => 'Productos',
                        'codigo' => 'INVENTARIO_PRODUCTOS',
                        'ruta' => 'inventario.productos.index',
                        'permiso_ver' => [
                            'nombre' => 'Ver listado de productos',
                            'codigo' => 'inventario.productos.ver',
                        ],
                        'botones' => [
                            [
                                'nombre' => 'Crear',
                                'codigo' => 'INVENTARIO_PRODUCTOS_CREAR',
                                'permiso_nombre' => 'Crear productos',
                                'permiso_codigo' => 'inventario.productos.crear',
                            ],
                            [
                                'nombre' => 'Editar',
                                'codigo' => 'INVENTARIO_PRODUCTOS_EDITAR',
                                'permiso_nombre' => 'Editar productos',
                                'permiso_codigo' => 'inventario.productos.editar',
                            ],
                        ],
                    ],
                    [
                        'nombre' => 'Reservacion Web',
                        'codigo' => 'INVENTARIO_RESERVACION',
                        'ruta' => 'inventario.reservacion',
                        'permiso_ver' => [
                            'nombre' => 'Ver vista previa de reservacion',
                            'codigo' => 'inventario.reservacion.ver',
                        ],
                    ],
                    [
                        'nombre' => 'Reporte de Catalogo',
                        'codigo' => 'INVENTARIO_REPORTES',
                        'ruta' => 'inventario.reportes',
                        'permiso_ver' => [
                            'nombre' => 'Ver reporte de inventario',
                            'codigo' => 'inventario.reportes.ver',
                        ],
                    ],
                ],
            ],
            [
                'nombre' => 'Finanzas',
                'codigo' => 'FINANZAS',
                'pantallas' => [
                    [
                        'nombre' => 'Dashboard Financiero',
                        'codigo' => 'FINANZAS_DASHBOARD',
                        'ruta' => 'finanzas.dashboard',
                        'permiso_ver' => [
                            'nombre' => 'Ver dashboard financiero',
                            'codigo' => 'finanzas.dashboard.ver',
                        ],
                    ],
                    [
                        'nombre' => 'Propietarios',
                        'codigo' => 'FINANZAS_PROPIETARIOS',
                        'ruta' => 'finanzas.propietarios.index',
                        'permiso_ver' => [
                            'nombre' => 'Ver propietarios financieros',
                            'codigo' => 'finanzas.propietarios.ver',
                        ],
                        'botones' => [
                            [
                                'nombre' => 'Crear',
                                'codigo' => 'FINANZAS_PROPIETARIOS_CREAR',
                                'permiso_nombre' => 'Crear propietarios financieros',
                                'permiso_codigo' => 'finanzas.propietarios.crear',
                            ],
                            [
                                'nombre' => 'Editar',
                                'codigo' => 'FINANZAS_PROPIETARIOS_EDITAR',
                                'permiso_nombre' => 'Editar propietarios financieros',
                                'permiso_codigo' => 'finanzas.propietarios.editar',
                            ],
                            [
                                'nombre' => 'Detalles',
                                'codigo' => 'FINANZAS_PROPIETARIOS_DETALLES',
                                'permiso_nombre' => 'Ver detalles de propietarios financieros',
                                'permiso_codigo' => 'finanzas.propietarios.detalles',
                            ],
                        ],
                    ],
                    [
                        'nombre' => 'Gastos Fijos',
                        'codigo' => 'FINANZAS_GASTOS_FIJOS',
                        'ruta' => 'finanzas.gastos-fijos.index',
                        'permiso_ver' => [
                            'nombre' => 'Ver gastos fijos',
                            'codigo' => 'finanzas.gastos-fijos.ver',
                        ],
                        'botones' => [
                            [
                                'nombre' => 'Crear',
                                'codigo' => 'FINANZAS_GASTOS_FIJOS_CREAR',
                                'permiso_nombre' => 'Crear gastos fijos',
                                'permiso_codigo' => 'finanzas.gastos-fijos.crear',
                            ],
                            [
                                'nombre' => 'Editar',
                                'codigo' => 'FINANZAS_GASTOS_FIJOS_EDITAR',
                                'permiso_nombre' => 'Editar gastos fijos',
                                'permiso_codigo' => 'finanzas.gastos-fijos.editar',
                            ],
                            [
                                'nombre' => 'Detalles',
                                'codigo' => 'FINANZAS_GASTOS_FIJOS_DETALLES',
                                'permiso_nombre' => 'Ver detalles de gastos fijos',
                                'permiso_codigo' => 'finanzas.gastos-fijos.detalles',
                            ],
                        ],
                    ],
                    [
                        'nombre' => 'Cuentas',
                        'codigo' => 'FINANZAS_CUENTAS',
                        'ruta' => 'finanzas.cuentas.index',
                        'permiso_ver' => [
                            'nombre' => 'Ver cuentas financieras',
                            'codigo' => 'finanzas.cuentas.ver',
                        ],
                        'botones' => [
                            [
                                'nombre' => 'Crear',
                                'codigo' => 'FINANZAS_CUENTAS_CREAR',
                                'permiso_nombre' => 'Crear cuentas financieras',
                                'permiso_codigo' => 'finanzas.cuentas.crear',
                            ],
                            [
                                'nombre' => 'Editar',
                                'codigo' => 'FINANZAS_CUENTAS_EDITAR',
                                'permiso_nombre' => 'Editar cuentas financieras',
                                'permiso_codigo' => 'finanzas.cuentas.editar',
                            ],
                            [
                                'nombre' => 'Detalles',
                                'codigo' => 'FINANZAS_CUENTAS_DETALLES',
                                'permiso_nombre' => 'Ver detalles de cuentas financieras',
                                'permiso_codigo' => 'finanzas.cuentas.detalles',
                            ],
                            [
                                'nombre' => 'Crear Bolsillos',
                                'codigo' => 'FINANZAS_BOLSILLOS_CREAR',
                                'permiso_nombre' => 'Crear bolsillos financieros',
                                'permiso_codigo' => 'finanzas.bolsillos.crear',
                            ],
                            [
                                'nombre' => 'Editar Bolsillos',
                                'codigo' => 'FINANZAS_BOLSILLOS_EDITAR',
                                'permiso_nombre' => 'Editar bolsillos financieros',
                                'permiso_codigo' => 'finanzas.bolsillos.editar',
                            ],
                            [
                                'nombre' => 'Desactivar Bolsillos',
                                'codigo' => 'FINANZAS_BOLSILLOS_DESACTIVAR',
                                'permiso_nombre' => 'Desactivar bolsillos financieros',
                                'permiso_codigo' => 'finanzas.bolsillos.desactivar',
                            ],
                            [
                                'nombre' => 'Distribuir Dinero',
                                'codigo' => 'FINANZAS_BOLSILLOS_DISTRIBUIR',
                                'permiso_nombre' => 'Distribuir dinero entre bolsillos',
                                'permiso_codigo' => 'finanzas.bolsillos.distribuir',
                            ],
                        ],
                    ],
                    [
                        'nombre' => 'Deudas',
                        'codigo' => 'FINANZAS_DEUDAS',
                        'ruta' => 'finanzas.deudas.index',
                        'permiso_ver' => [
                            'nombre' => 'Ver deudas',
                            'codigo' => 'finanzas.deudas.ver',
                        ],
                        'botones' => [
                            [
                                'nombre' => 'Crear',
                                'codigo' => 'FINANZAS_DEUDAS_CREAR',
                                'permiso_nombre' => 'Crear deudas',
                                'permiso_codigo' => 'finanzas.deudas.crear',
                            ],
                            [
                                'nombre' => 'Editar',
                                'codigo' => 'FINANZAS_DEUDAS_EDITAR',
                                'permiso_nombre' => 'Editar deudas',
                                'permiso_codigo' => 'finanzas.deudas.editar',
                            ],
                            [
                                'nombre' => 'Detalles',
                                'codigo' => 'FINANZAS_DEUDAS_DETALLES',
                                'permiso_nombre' => 'Ver detalles de deudas',
                                'permiso_codigo' => 'finanzas.deudas.detalles',
                            ],
                            [
                                'nombre' => 'Pagar',
                                'codigo' => 'FINANZAS_DEUDAS_PAGAR',
                                'permiso_nombre' => 'Pagar deudas',
                                'permiso_codigo' => 'finanzas.deudas.pagar',
                            ],
                        ],
                    ],
                    [
                        'nombre' => 'Cuentas por cobrar',
                        'codigo' => 'FINANZAS_CUENTAS_POR_COBRAR',
                        'ruta' => 'finanzas.cuentas-por-cobrar.index',
                        'permiso_ver' => [
                            'nombre' => 'Ver cuentas por cobrar',
                            'codigo' => 'finanzas.cuentas-por-cobrar.ver',
                        ],
                        'botones' => [
                            [
                                'nombre' => 'Crear',
                                'codigo' => 'FINANZAS_CUENTAS_POR_COBRAR_CREAR',
                                'permiso_nombre' => 'Crear cuentas por cobrar',
                                'permiso_codigo' => 'finanzas.cuentas-por-cobrar.crear',
                            ],
                            [
                                'nombre' => 'Editar',
                                'codigo' => 'FINANZAS_CUENTAS_POR_COBRAR_EDITAR',
                                'permiso_nombre' => 'Editar cuentas por cobrar',
                                'permiso_codigo' => 'finanzas.cuentas-por-cobrar.editar',
                            ],
                            [
                                'nombre' => 'Detalles',
                                'codigo' => 'FINANZAS_CUENTAS_POR_COBRAR_DETALLES',
                                'permiso_nombre' => 'Ver detalles de cuentas por cobrar',
                                'permiso_codigo' => 'finanzas.cuentas-por-cobrar.detalles',
                            ],
                            [
                                'nombre' => 'Cobrar',
                                'codigo' => 'FINANZAS_CUENTAS_POR_COBRAR_COBRAR',
                                'permiso_nombre' => 'Cobrar cuentas por cobrar',
                                'permiso_codigo' => 'finanzas.cuentas-por-cobrar.cobrar',
                            ],
                        ],
                    ],
                    [
                        'nombre' => 'Ingresos Fijos',
                        'codigo' => 'FINANZAS_INGRESOS_FIJOS',
                        'ruta' => 'finanzas.ingresos-fijos.index',
                        'permiso_ver' => [
                            'nombre' => 'Ver ingresos fijos',
                            'codigo' => 'finanzas.ingresos-fijos.ver',
                        ],
                        'botones' => [
                            [
                                'nombre' => 'Crear',
                                'codigo' => 'FINANZAS_INGRESOS_FIJOS_CREAR',
                                'permiso_nombre' => 'Crear ingresos fijos',
                                'permiso_codigo' => 'finanzas.ingresos-fijos.crear',
                            ],
                            [
                                'nombre' => 'Editar',
                                'codigo' => 'FINANZAS_INGRESOS_FIJOS_EDITAR',
                                'permiso_nombre' => 'Editar ingresos fijos',
                                'permiso_codigo' => 'finanzas.ingresos-fijos.editar',
                            ],
                            [
                                'nombre' => 'Detalles',
                                'codigo' => 'FINANZAS_INGRESOS_FIJOS_DETALLES',
                                'permiso_nombre' => 'Ver detalles de ingresos fijos',
                                'permiso_codigo' => 'finanzas.ingresos-fijos.detalles',
                            ],
                        ],
                    ],
                    [
                        'nombre' => 'Movimientos de Cuenta',
                        'codigo' => 'FINANZAS_MOVIMIENTOS',
                        'ruta' => 'finanzas.movimientos.index',
                        'permiso_ver' => [
                            'nombre' => 'Ver movimientos de cuenta',
                            'codigo' => 'finanzas.movimientos.ver',
                        ],
                        'botones' => [
                            [
                                'nombre' => 'Crear',
                                'codigo' => 'FINANZAS_MOVIMIENTOS_CREAR',
                                'permiso_nombre' => 'Crear movimientos de cuenta',
                                'permiso_codigo' => 'finanzas.movimientos.crear',
                            ],
                            [
                                'nombre' => 'Editar',
                                'codigo' => 'FINANZAS_MOVIMIENTOS_EDITAR',
                                'permiso_nombre' => 'Editar movimientos de cuenta',
                                'permiso_codigo' => 'finanzas.movimientos.editar',
                            ],
                            [
                                'nombre' => 'Detalles',
                                'codigo' => 'FINANZAS_MOVIMIENTOS_DETALLES',
                                'permiso_nombre' => 'Ver detalles de movimientos de cuenta',
                                'permiso_codigo' => 'finanzas.movimientos.detalles',
                            ],
                        ],
                    ],
                    [
                        'nombre' => 'Reportes Financieros',
                        'codigo' => 'FINANZAS_REPORTES',
                        'ruta' => 'finanzas.reportes',
                        'permiso_ver' => [
                            'nombre' => 'Ver reportes financieros',
                            'codigo' => 'finanzas.reportes.ver',
                        ],
                    ],
                ],
            ],
        ];

        foreach ($catalogo as $moduloData) {
            DB::table('modulos')->updateOrInsert(
                ['codigo' => $moduloData['codigo']],
                [
                    'nombre' => $moduloData['nombre'],
                    'updated_at' => $now,
                    'created_at' => $now,
                ],
            );

            $moduloId = DB::table('modulos')
                ->where('codigo', $moduloData['codigo'])
                ->value('id');

            foreach ($moduloData['pantallas'] as $pantallaData) {
                DB::table('pantallas')->updateOrInsert(
                    ['codigo' => $pantallaData['codigo']],
                    [
                        'nombre' => $pantallaData['nombre'],
                        'modulo_id' => $moduloId,
                        'ruta' => $pantallaData['ruta'],
                        'updated_at' => $now,
                        'created_at' => $now,
                    ],
                );

                $pantallaId = DB::table('pantallas')
                    ->where('codigo', $pantallaData['codigo'])
                    ->value('id');

                DB::table('permisos')->updateOrInsert(
                    ['codigo' => $pantallaData['permiso_ver']['codigo']],
                    [
                        'modulo_id' => $moduloId,
                        'pantalla_id' => $pantallaId,
                        'boton_id' => null,
                        'nombre' => $pantallaData['permiso_ver']['nombre'],
                        'updated_at' => $now,
                        'created_at' => $now,
                    ],
                );

                foreach (($pantallaData['botones'] ?? []) as $botonData) {
                    DB::table('botones')->updateOrInsert(
                        [
                            'pantalla_id' => $pantallaId,
                            'codigo' => $botonData['codigo'],
                        ],
                        [
                            'nombre' => $botonData['nombre'],
                            'updated_at' => $now,
                            'created_at' => $now,
                        ],
                    );

                    $botonId = DB::table('botones')
                        ->where('pantalla_id', $pantallaId)
                        ->where('codigo', $botonData['codigo'])
                        ->value('id');

                    DB::table('permisos')->updateOrInsert(
                        ['codigo' => $botonData['permiso_codigo']],
                        [
                            'modulo_id' => $moduloId,
                            'pantalla_id' => $pantallaId,
                            'boton_id' => $botonId,
                            'nombre' => $botonData['permiso_nombre'],
                            'updated_at' => $now,
                            'created_at' => $now,
                        ],
                    );
                }
            }
        }

        $allPermissionIds = DB::table('permisos')->pluck('id');

        DB::table('roles')
            ->where('nombre', 'General')
            ->pluck('id')
            ->each(function ($roleId) use ($allPermissionIds, $now): void {
                foreach ($allPermissionIds as $permissionId) {
                    DB::table('rol_permiso')->updateOrInsert(
                        [
                            'rol_id' => $roleId,
                            'permiso_id' => $permissionId,
                        ],
                        [
                            'created_at' => $now,
                            'updated_at' => $now,
                        ],
                    );
                }
            });
    }
}

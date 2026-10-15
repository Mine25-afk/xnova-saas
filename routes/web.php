<?php

use App\Http\Controllers\Citas\ReservaPublicaController;
use App\Http\Controllers\EmpresaController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\ListadosController;
use App\Http\Controllers\PayPalWebhookController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PuestoController;
use App\Http\Controllers\RolController;
use App\Http\Controllers\SocioNegocioController;
use App\Http\Controllers\SucursalController;
use App\Http\Controllers\SuperAdminController;
use App\Http\Controllers\UsuarioController;
use App\Http\Middleware\ResolvePublicTenant;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::post('/paypal/webhook', PayPalWebhookController::class)->name('paypal.webhook');

// Reserva pública: la empresa sale del uid de la URL (sin sesión).
Route::middleware(ResolvePublicTenant::class)->group(function (): void {
    Route::get('/reservar/{uid}', [ReservaPublicaController::class, 'show'])->name('reservaciones.public.form');
    Route::post('/reservar/{uid}', [ReservaPublicaController::class, 'store'])->name('reservaciones.public.store');
});

// Superadministración: transversal a las empresas, por eso no pasa por el middleware de tenant.
Route::middleware(['auth', 'superadmin'])->prefix('/superadmin')->name('superadmin.')->group(function (): void {
    Route::get('/empresas', [SuperAdminController::class, 'empresas'])->name('empresas.index');
    Route::get('/empresas/{xnova}', [SuperAdminController::class, 'showEmpresa'])->name('empresas.show');
});

Route::middleware('auth')->group(function (): void {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Todo lo de aquí abajo es dato de una empresa: "tenant" fija la empresa del usuario o responde 403.
Route::middleware(['auth', 'tenant'])->group(function (): void {
    Route::view('/dashboard', 'dashboard')->name('dashboard');

    Route::prefix('/sucursales-listado')->name('sucursales.')->group(function (): void {
        Route::get('/', [SucursalController::class, 'listado'])->middleware('permission:configuracion.sucursales.ver')->name('listado');
        Route::get('/data', [SucursalController::class, 'data'])->middleware('permission:configuracion.sucursales.ver')->name('data');
        Route::get('/nuevo', [SucursalController::class, 'create'])->middleware('permission:configuracion.sucursales.crear')->name('create');
        Route::post('/', [SucursalController::class, 'store'])->middleware('permission:configuracion.sucursales.crear')->name('store');
        Route::get('/{sucursal}/detalles', [SucursalController::class, 'show'])->middleware('permission:configuracion.sucursales.detalles')->name('show');
        Route::get('/{sucursal}/editar', [SucursalController::class, 'edit'])->middleware('permission:configuracion.sucursales.editar')->name('edit');
        Route::put('/{sucursal}', [SucursalController::class, 'update'])->middleware('permission:configuracion.sucursales.editar')->name('update');
    });

    Route::prefix('/roles-listado')->name('roles.')->group(function (): void {
        Route::get('/', [RolController::class, 'listado'])->middleware('permission:seguridad.roles.ver')->name('listado');
        Route::get('/data', [RolController::class, 'data'])->middleware('permission:seguridad.roles.ver')->name('data');
        Route::get('/nuevo', [RolController::class, 'create'])->middleware('permission:seguridad.roles.crear')->name('create');
        Route::post('/', [RolController::class, 'store'])->middleware('permission:seguridad.roles.crear')->name('store');
        Route::get('/{rol}/detalles', [RolController::class, 'show'])->middleware('permission:seguridad.roles.detalles')->name('show');
        Route::get('/{rol}/editar', [RolController::class, 'edit'])->middleware('permission:seguridad.roles.editar')->name('edit');
        Route::put('/{rol}', [RolController::class, 'update'])->middleware('permission:seguridad.roles.editar')->name('update');
    });

    Route::prefix('/usuarios-listado')->name('usuarios.')->group(function (): void {
        Route::get('/', [UsuarioController::class, 'listado'])->middleware('permission:seguridad.usuarios.ver')->name('listado');
        Route::get('/data', [UsuarioController::class, 'data'])->middleware('permission:seguridad.usuarios.ver')->name('data');
        Route::get('/nuevo', [UsuarioController::class, 'create'])->middleware('permission:seguridad.usuarios.crear')->name('create');
        Route::post('/', [UsuarioController::class, 'store'])->middleware('permission:seguridad.usuarios.crear')->name('store');
        Route::get('/{usuario}/detalles', [UsuarioController::class, 'show'])->middleware('permission:seguridad.usuarios.detalles')->name('show');
        Route::get('/{usuario}/editar', [UsuarioController::class, 'edit'])->middleware('permission:seguridad.usuarios.editar')->name('edit');
        Route::put('/{usuario}', [UsuarioController::class, 'update'])->middleware('permission:seguridad.usuarios.editar')->name('update');
        Route::patch('/{usuario}/activar', [UsuarioController::class, 'activate'])->middleware('permission:seguridad.usuarios.activar')->name('activate');
        Route::delete('/{usuario}', [UsuarioController::class, 'destroy'])->middleware('permission:seguridad.usuarios.desactivar')->name('deactivate');
    });

    Route::get('/listados/{tipo}', [ListadosController::class, 'obtener'])->name('listados.obtener');

    Route::prefix('/clientes-listado')->name('socios.clientes.')->group(function (): void {
        Route::get('/', [SocioNegocioController::class, 'listado_clientes'])->middleware('permission:socios.clientes.ver')->name('listado');
        Route::get('/data', [SocioNegocioController::class, 'data_clientes'])->middleware('permission:socios.clientes.ver')->name('data');
    });

    Route::prefix('/colaboradores-listado')->name('socios.colaboradores.')->group(function (): void {
        Route::get('/', [SocioNegocioController::class, 'listado_colaboradores'])->middleware('permission:socios.colaboradores.ver')->name('listado');
        Route::get('/data', [SocioNegocioController::class, 'data_colaboradores'])->middleware('permission:socios.colaboradores.ver')->name('data');
    });

    Route::prefix('/socios')->name('socios.')->group(function (): void {
        Route::get('/{tipo}/nuevo', [SocioNegocioController::class, 'create'])->name('create');
        Route::post('/', [SocioNegocioController::class, 'store'])->name('store');
        Route::get('/{socio}/detalles', [SocioNegocioController::class, 'show'])->name('show');
        Route::get('/{socio}/editar', [SocioNegocioController::class, 'edit'])->name('edit');
        Route::put('/{socio}', [SocioNegocioController::class, 'update'])->name('update');
        Route::patch('/{socio}/activar', [SocioNegocioController::class, 'activate'])->name('activate');
        Route::delete('/{socio}', [SocioNegocioController::class, 'destroy'])->name('deactivate');
    });

    Route::prefix('/puestos-list')->name('puestos.')->group(function (): void {
        Route::get('/', [PuestoController::class, 'page'])->middleware('permission:configuracion.puestos.ver')->name('page');
        Route::get('/data', [PuestoController::class, 'data'])->middleware('permission:configuracion.puestos.ver')->name('data');
        Route::get('/nuevo', [PuestoController::class, 'create'])->middleware('permission:configuracion.puestos.crear')->name('create');
        Route::post('/', [PuestoController::class, 'store'])->middleware('permission:configuracion.puestos.crear')->name('store');
        Route::get('/{puesto}/editar', [PuestoController::class, 'edit'])->middleware('permission:configuracion.puestos.editar')->name('edit');
        Route::put('/{puesto}', [PuestoController::class, 'update'])->middleware('permission:configuracion.puestos.editar')->name('update');
        Route::patch('/{puesto}/activar', [PuestoController::class, 'activate'])->middleware('permission:configuracion.puestos.activar')->name('activate');
        Route::delete('/{puesto}', [PuestoController::class, 'destroy'])->middleware('permission:configuracion.puestos.desactivar')->name('deactivate');
    });

    require __DIR__.'/citas.php';

    Route::prefix('/inventario')->name('inventario.')->group(function (): void {
        Route::prefix('/productos')->name('productos.')->group(function (): void {
            Route::get('/', [ProductoController::class, 'index'])->middleware('permission:inventario.productos.ver')->name('index');
            Route::get('/data', [ProductoController::class, 'data'])->middleware('permission:inventario.productos.ver')->name('data');
            Route::get('/nuevo', [ProductoController::class, 'create'])->middleware('permission:inventario.productos.crear')->name('create');
            Route::post('/', [ProductoController::class, 'store'])->middleware('permission:inventario.productos.crear')->name('store');
            Route::get('/{producto}/editar', [ProductoController::class, 'edit'])->middleware('permission:inventario.productos.editar')->name('edit');
            Route::put('/{producto}', [ProductoController::class, 'update'])->middleware('permission:inventario.productos.editar')->name('update');
        });

        Route::get('/reservacion', [InventoryController::class, 'reservation'])->middleware('permission:inventario.reservacion.ver')->name('reservacion');
        Route::get('/reportes', [InventoryController::class, 'reportes'])->middleware('permission:inventario.reportes.ver')->name('reportes');
    });

    require __DIR__.'/finanzas.php';

    Route::prefix('/empresa')->name('empresa.')->group(function (): void {
        Route::get('/preferencias', [EmpresaController::class, 'preferencias'])->name('preferencias');
        Route::patch('/preferencias', [EmpresaController::class, 'actualizarPreferencias'])->name('preferencias.update');
    });
});

require __DIR__.'/auth.php';

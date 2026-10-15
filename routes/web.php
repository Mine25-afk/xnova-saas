<?php

use App\Http\Controllers\EmpresaController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PayPalWebhookController;
use App\Http\Controllers\CitaController;
use App\Http\Controllers\FinanzasController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\ListadosController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\PuestoController;
use App\Http\Controllers\PublicReservationController;
use App\Http\Controllers\RolController;
use App\Http\Controllers\SocioNegocioController;
use App\Http\Controllers\SucursalController;
use App\Http\Controllers\SuperAdminController;
use App\Http\Controllers\UsuarioController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::post('/paypal/webhook', PayPalWebhookController::class)
    ->name('paypal.webhook');

Route::get('/reservar/{uid}', [PublicReservationController::class, 'show'])->name('reservaciones.public.form');
Route::post('/reservar/{uid}', [PublicReservationController::class, 'store'])->name('reservaciones.public.store');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::prefix('/sucursales-listado')->name('sucursales.')->group(function () {
        Route::get('/', [SucursalController::class, 'listado'])->middleware('permission:configuracion.sucursales.ver')->name('listado');
        Route::get('/data', [SucursalController::class,'data'])->middleware('permission:configuracion.sucursales.ver')->name('data');
        Route::get('/nuevo', [SucursalController::class, 'create'])->middleware('permission:configuracion.sucursales.crear')->name('create');
        Route::post('/', [SucursalController::class, 'store'])->middleware('permission:configuracion.sucursales.crear')->name('store');
        Route::get('/{sucursal}/detalles', [SucursalController::class, 'show'])->middleware('permission:configuracion.sucursales.detalles')->name('show');
        Route::get('/{sucursal}/editar', [SucursalController::class, 'edit'])->middleware('permission:configuracion.sucursales.editar')->name('edit');
        Route::put('/{sucursal}', [SucursalController::class, 'update'])->middleware('permission:configuracion.sucursales.editar')->name('update');
    });

    Route::prefix('/roles-listado')->name('roles.')->group(function () {
        Route::get('/', [RolController::class, 'listado'])->middleware('permission:seguridad.roles.ver')->name('listado');
        Route::get('/data', [RolController::class,'data'])->middleware('permission:seguridad.roles.ver')->name('data');
        Route::get('/nuevo', [RolController::class, 'create'])->middleware('permission:seguridad.roles.crear')->name('create');
        Route::post('/', [RolController::class, 'store'])->middleware('permission:seguridad.roles.crear')->name('store');
        Route::get('/{rol}/detalles', [RolController::class, 'show'])->middleware('permission:seguridad.roles.detalles')->name('show');
        Route::get('/{rol}/editar', [RolController::class, 'edit'])->middleware('permission:seguridad.roles.editar')->name('edit');
        Route::put('/{rol}', [RolController::class, 'update'])->middleware('permission:seguridad.roles.editar')->name('update');
    });

    Route::prefix('/usuarios-listado')->name('usuarios.')->group(function () {
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

    Route::prefix('/listados')->name('listados.')->group(function () {
        Route::get('/{tipo}', [ListadosController::class, 'obtener'])->name('obtener');
    });

    Route::prefix('/clientes-listado')->name('socios.clientes.')->group(function () {
        Route::get('/', [SocioNegocioController::class, 'listado_clientes'])->middleware('permission:socios.clientes.ver')->name('listado');
        Route::get('/data', [SocioNegocioController::class,'data_clientes'])->middleware('permission:socios.clientes.ver')->name('data');
    });

    Route::prefix('/colaboradores-listado')->name('socios.colaboradores.')->group(function () {
        Route::get('/', [SocioNegocioController::class, 'listado_colaboradores'])->middleware('permission:socios.colaboradores.ver')->name('listado');
        Route::get('/data', [SocioNegocioController::class,'data_colaboradores'])->middleware('permission:socios.colaboradores.ver')->name('data');
    });

    Route::prefix('/socios')->name('socios.')->group(function () {
        Route::get('/{tipo}/nuevo', [SocioNegocioController::class, 'create'])->name('create');
        Route::post('/', [SocioNegocioController::class, 'store'])->name('store');
        Route::get('/{socio}/detalles', [SocioNegocioController::class, 'show'])->name('show');
        Route::get('/{socio}/editar', [SocioNegocioController::class, 'edit'])->name('edit');
        Route::put('/{socio}', [SocioNegocioController::class, 'update'])->name('update');
        Route::patch('/{socio}/activar', [SocioNegocioController::class, 'activate'])->name('activate');
        Route::delete('/{socio}', [SocioNegocioController::class, 'destroy'])->name('deactivate');
    });

    Route::prefix('/puestos-list')->name('puestos.')->group(function () {
        Route::get('/', [PuestoController::class, 'page'])->middleware('permission:configuracion.puestos.ver')->name('page');
        Route::get('/data', [PuestoController::class, 'data'])->middleware('permission:configuracion.puestos.ver')->name('data');
        Route::get('/nuevo', [PuestoController::class, 'create'])->middleware('permission:configuracion.puestos.crear')->name('create');
        Route::post('/', [PuestoController::class, 'store'])->middleware('permission:configuracion.puestos.crear')->name('store');
        Route::get('/{puesto}/editar', [PuestoController::class, 'edit'])->middleware('permission:configuracion.puestos.editar')->name('edit');
        Route::put('/{puesto}', [PuestoController::class, 'update'])->middleware('permission:configuracion.puestos.editar')->name('update');
        Route::patch('/{puesto}/activar', [PuestoController::class, 'activate'])->middleware('permission:configuracion.puestos.activar')->name('activate');
        Route::delete('/{puesto}', [PuestoController::class, 'destroy'])->middleware('permission:configuracion.puestos.desactivar')->name('deactivate');
    });

    Route::prefix('/citas')->name('citas.')->group(function () {
        Route::get('/', [CitaController::class, 'listado'])->middleware('permission:citas.ver')->name('listado');
        Route::get('/data', [CitaController::class, 'data'])->middleware('permission:citas.ver')->name('data');
        Route::get('/huecos-libres', [CitaController::class, 'freeSlots'])->middleware('permission:citas.ver')->name('free-slots');
        Route::get('/nuevo', [CitaController::class, 'create'])->middleware('permission:citas.crear')->name('create');
        Route::post('/', [CitaController::class, 'store'])->middleware('permission:citas.crear')->name('store');
        Route::get('/calendario', [CitaController::class, 'calendario'])->middleware('permission:citas.calendario.ver')->name('calendario');
        Route::get('/reportes', [CitaController::class, 'reportes'])->middleware('permission:citas.reportes.ver')->name('reportes');
        Route::get('/{cita}/detalles', [CitaController::class, 'show'])->middleware('permission:citas.detalles')->name('show');
        Route::get('/{cita}/editar', [CitaController::class, 'edit'])->middleware('permission:citas.editar')->name('edit');
        Route::put('/{cita}', [CitaController::class, 'update'])->middleware('permission:citas.editar')->name('update');
        Route::patch('/{cita}/terminar', [CitaController::class, 'finish'])->middleware('permission:citas.terminar')->name('finish');
        Route::patch('/{cita}/cancelar', [CitaController::class, 'cancel'])->middleware('permission:citas.cancelar')->name('cancel');
    });

    Route::prefix('/inventario')->name('inventario.')->group(function () {
        Route::prefix('/productos')->name('productos.')->group(function () {
            Route::get('/', [ProductoController::class, 'index'])->middleware('permission:inventario.productos.ver')->name('index');
            Route::get('/data', [ProductoController::class, 'data'])->middleware('permission:inventario.productos.ver')->name('data');
            Route::get('/nuevo', [ProductoController::class, 'create'])->middleware('permission:inventario.productos.crear')->name('create');
            Route::post('/', [ProductoController::class, 'store'])->middleware('permission:inventario.productos.crear')->name('store');
            Route::get('/{producto}/editar', [ProductoController::class, 'edit'])->middleware('permission:inventario.productos.editar')->name('edit');
            Route::put('/{producto}', [ProductoController::class, 'update'])->middleware('permission:inventario.productos.editar')->name('update');
        });

        Route::get('/reservacion', [InventoryController::class, 'reservation'])
            ->middleware('permission:inventario.reservacion.ver')
            ->name('reservacion');

        Route::get('/reportes', [InventoryController::class, 'reportes'])
            ->middleware('permission:inventario.reportes.ver')
            ->name('reportes');
    });

    Route::prefix('/finanzas')->name('finanzas.')->group(function () {
        Route::get('/', [FinanzasController::class, 'dashboard'])->middleware('permission:finanzas.dashboard.ver')->name('dashboard');

        // Captura rápida (FAB global): registrar un gasto/ingreso en 3 toques.
        Route::get('/quick/opciones', [FinanzasController::class, 'quickOpciones'])->middleware('permission:finanzas.movimientos.crear')->name('quick.opciones');
        Route::post('/quick', [FinanzasController::class, 'quickStore'])->middleware('permission:finanzas.movimientos.crear')->name('quick.store');

        // Operaciones pendientes de confirmación (transferencias, pagos de deuda, cobros).
        Route::prefix('/pendientes')->name('pendientes.')->group(function () {
            Route::get('/', [FinanzasController::class, 'pendientes'])->middleware('permission:finanzas.movimientos.ver')->name('index');
            Route::post('/{movimiento}/confirmar', [FinanzasController::class, 'confirmarPendiente'])->middleware('permission:finanzas.movimientos.crear')->name('confirmar');
            Route::post('/{movimiento}/anular', [FinanzasController::class, 'anularPendiente'])->middleware('permission:finanzas.movimientos.crear')->name('anular');
        });

        Route::prefix('/propietarios')->name('propietarios.')->group(function () {
            Route::get('/', [FinanzasController::class, 'propietarios'])->middleware('permission:finanzas.propietarios.ver')->name('index');
            Route::get('/data', [FinanzasController::class, 'propietariosData'])->middleware('permission:finanzas.propietarios.ver')->name('data');
            Route::get('/nuevo', [FinanzasController::class, 'createPropietario'])->middleware('permission:finanzas.propietarios.crear')->name('create');
            Route::post('/', [FinanzasController::class, 'storePropietario'])->middleware('permission:finanzas.propietarios.crear')->name('store');
            Route::get('/{propietario}/detalles', [FinanzasController::class, 'showPropietario'])->middleware('permission:finanzas.propietarios.detalles')->name('show');
            Route::get('/{propietario}/editar', [FinanzasController::class, 'editPropietario'])->middleware('permission:finanzas.propietarios.editar')->name('edit');
            Route::put('/{propietario}', [FinanzasController::class, 'updatePropietario'])->middleware('permission:finanzas.propietarios.editar')->name('update');
        });

        Route::prefix('/gastos-fijos')->name('gastos-fijos.')->group(function () {
            Route::get('/', [FinanzasController::class, 'gastosFijos'])->middleware('permission:finanzas.gastos-fijos.ver')->name('index');
            Route::get('/data', [FinanzasController::class, 'gastosFijosData'])->middleware('permission:finanzas.gastos-fijos.ver')->name('data');
            Route::get('/nuevo', [FinanzasController::class, 'createGastoFijo'])->middleware('permission:finanzas.gastos-fijos.crear')->name('create');
            Route::post('/', [FinanzasController::class, 'storeGastoFijo'])->middleware('permission:finanzas.gastos-fijos.crear')->name('store');
            Route::get('/{gasto}/detalles', [FinanzasController::class, 'showGastoFijo'])->middleware('permission:finanzas.gastos-fijos.detalles')->name('show');
            Route::get('/{gasto}/editar', [FinanzasController::class, 'editGastoFijo'])->middleware('permission:finanzas.gastos-fijos.editar')->name('edit');
            Route::put('/{gasto}', [FinanzasController::class, 'updateGastoFijo'])->middleware('permission:finanzas.gastos-fijos.editar')->name('update');
        });

        Route::prefix('/cuentas')->name('cuentas.')->group(function () {
            Route::get('/', [FinanzasController::class, 'cuentas'])->middleware('permission:finanzas.cuentas.ver')->name('index');
            Route::get('/data', [FinanzasController::class, 'cuentasData'])->middleware('permission:finanzas.cuentas.ver')->name('data');
            Route::get('/nuevo', [FinanzasController::class, 'createCuenta'])->middleware('permission:finanzas.cuentas.crear')->name('create');
            Route::post('/', [FinanzasController::class, 'storeCuenta'])->middleware('permission:finanzas.cuentas.crear')->name('store');
            Route::get('/{cuenta}/detalles', [FinanzasController::class, 'showCuenta'])->middleware('permission:finanzas.cuentas.detalles')->name('show');
            Route::get('/{cuenta}/editar', [FinanzasController::class, 'editCuenta'])->middleware('permission:finanzas.cuentas.editar')->name('edit');
            Route::put('/{cuenta}', [FinanzasController::class, 'updateCuenta'])->middleware('permission:finanzas.cuentas.editar')->name('update');
            Route::get('/{cuenta}/bolsillos/nuevo', [FinanzasController::class, 'createBolsillo'])->middleware('permission:finanzas.bolsillos.crear')->name('bolsillos.create');
            Route::post('/{cuenta}/bolsillos', [FinanzasController::class, 'storeBolsillo'])->middleware('permission:finanzas.bolsillos.crear')->name('bolsillos.store');
            Route::get('/{cuenta}/bolsillos/{bolsillo}/editar', [FinanzasController::class, 'editBolsillo'])->middleware('permission:finanzas.bolsillos.editar')->name('bolsillos.edit');
            Route::put('/{cuenta}/bolsillos/{bolsillo}', [FinanzasController::class, 'updateBolsillo'])->middleware('permission:finanzas.bolsillos.editar')->name('bolsillos.update');
            Route::patch('/{cuenta}/bolsillos/{bolsillo}/desactivar', [FinanzasController::class, 'deactivateBolsillo'])->middleware('permission:finanzas.bolsillos.desactivar')->name('bolsillos.deactivate');
            Route::post('/{cuenta}/distribuir', [FinanzasController::class, 'distributeBolsillos'])->middleware('permission:finanzas.bolsillos.distribuir')->name('bolsillos.distribute');
            Route::post('/{cuenta}/ajustar', [FinanzasController::class, 'ajustarCuenta'])->middleware('permission:finanzas.movimientos.crear')->name('ajustar');

            // Pantalla detalle de subcuenta con acciones rápidas (ingreso, gasto, transferencia)
            Route::get('/{cuenta}/subcuentas/{subcuenta}', [FinanzasController::class, 'showSubcuenta'])->middleware('permission:finanzas.cuentas.detalles')->name('subcuentas.show');
            Route::post('/{cuenta}/subcuentas/{subcuenta}/ingreso', [FinanzasController::class, 'storeIngresoSubcuenta'])->middleware('permission:finanzas.movimientos.crear')->name('subcuentas.ingreso');
            Route::post('/{cuenta}/subcuentas/{subcuenta}/gasto', [FinanzasController::class, 'storeGastoSubcuenta'])->middleware('permission:finanzas.movimientos.crear')->name('subcuentas.gasto');
            Route::post('/{cuenta}/subcuentas/{subcuenta}/transferir', [FinanzasController::class, 'storeTransferenciaSubcuenta'])->middleware('permission:finanzas.movimientos.crear')->name('subcuentas.transferir');
            Route::post('/{cuenta}/subcuentas/{subcuenta}/pagar-deuda', [FinanzasController::class, 'storePagoDeudaSubcuenta'])->middleware('permission:finanzas.deudas.pagar')->name('subcuentas.pagar-deuda');
            Route::post('/{cuenta}/subcuentas/{subcuenta}/movimientos/{movimiento}/anular', [FinanzasController::class, 'anularMovimientoSubcuenta'])->middleware('permission:finanzas.movimientos.crear')->name('subcuentas.movimientos.anular');
        });

        Route::prefix('/deudas')->name('deudas.')->group(function () {
            Route::get('/', [FinanzasController::class, 'deudas'])->middleware('permission:finanzas.deudas.ver')->name('index');
            Route::get('/data', [FinanzasController::class, 'deudasData'])->middleware('permission:finanzas.deudas.ver')->name('data');
            Route::get('/nuevo', [FinanzasController::class, 'createDeuda'])->middleware('permission:finanzas.deudas.crear')->name('create');
            Route::post('/', [FinanzasController::class, 'storeDeuda'])->middleware('permission:finanzas.deudas.crear')->name('store');
            Route::get('/{deuda}/detalles', [FinanzasController::class, 'showDeuda'])->middleware('permission:finanzas.deudas.detalles')->name('show');
            Route::get('/{deuda}/editar', [FinanzasController::class, 'editDeuda'])->middleware('permission:finanzas.deudas.editar')->name('edit');
            Route::put('/{deuda}', [FinanzasController::class, 'updateDeuda'])->middleware('permission:finanzas.deudas.editar')->name('update');
            Route::post('/{deuda}/pagar', [FinanzasController::class, 'storePagoDeuda'])->middleware('permission:finanzas.deudas.pagar')->name('pagar');
        });

        Route::prefix('/cuentas-por-cobrar')->name('cuentas-por-cobrar.')->group(function () {
            Route::get('/', [FinanzasController::class, 'cuentasPorCobrar'])->middleware('permission:finanzas.cuentas-por-cobrar.ver')->name('index');
            Route::get('/data', [FinanzasController::class, 'cuentasPorCobrarData'])->middleware('permission:finanzas.cuentas-por-cobrar.ver')->name('data');
            Route::get('/nuevo', [FinanzasController::class, 'createCuentaPorCobrar'])->middleware('permission:finanzas.cuentas-por-cobrar.crear')->name('create');
            Route::post('/', [FinanzasController::class, 'storeCuentaPorCobrar'])->middleware('permission:finanzas.cuentas-por-cobrar.crear')->name('store');
            Route::get('/{cuentaPorCobrar}/detalles', [FinanzasController::class, 'showCuentaPorCobrar'])->middleware('permission:finanzas.cuentas-por-cobrar.detalles')->name('show');
            Route::get('/{cuentaPorCobrar}/editar', [FinanzasController::class, 'editCuentaPorCobrar'])->middleware('permission:finanzas.cuentas-por-cobrar.editar')->name('edit');
            Route::put('/{cuentaPorCobrar}', [FinanzasController::class, 'updateCuentaPorCobrar'])->middleware('permission:finanzas.cuentas-por-cobrar.editar')->name('update');
            Route::post('/{cuentaPorCobrar}/cobrar', [FinanzasController::class, 'storeCobro'])->middleware('permission:finanzas.cuentas-por-cobrar.cobrar')->name('cobrar');
        });

        Route::prefix('/ingresos-fijos')->name('ingresos-fijos.')->group(function () {
            Route::get('/', [FinanzasController::class, 'ingresosFijos'])->middleware('permission:finanzas.ingresos-fijos.ver')->name('index');
            Route::get('/data', [FinanzasController::class, 'ingresosFijosData'])->middleware('permission:finanzas.ingresos-fijos.ver')->name('data');
            Route::get('/nuevo', [FinanzasController::class, 'createIngresoFijo'])->middleware('permission:finanzas.ingresos-fijos.crear')->name('create');
            Route::post('/', [FinanzasController::class, 'storeIngresoFijo'])->middleware('permission:finanzas.ingresos-fijos.crear')->name('store');
            Route::get('/{ingreso}/detalles', [FinanzasController::class, 'showIngresoFijo'])->middleware('permission:finanzas.ingresos-fijos.detalles')->name('show');
            Route::get('/{ingreso}/editar', [FinanzasController::class, 'editIngresoFijo'])->middleware('permission:finanzas.ingresos-fijos.editar')->name('edit');
            Route::put('/{ingreso}', [FinanzasController::class, 'updateIngresoFijo'])->middleware('permission:finanzas.ingresos-fijos.editar')->name('update');
        });

        Route::prefix('/movimientos')->name('movimientos.')->group(function () {
            Route::get('/', [FinanzasController::class, 'movimientos'])->middleware('permission:finanzas.movimientos.ver')->name('index');
            Route::get('/data', [FinanzasController::class, 'movimientosData'])->middleware('permission:finanzas.movimientos.ver')->name('data');
            Route::get('/nuevo', [FinanzasController::class, 'createMovimiento'])->middleware('permission:finanzas.movimientos.crear')->name('create');
            Route::post('/', [FinanzasController::class, 'storeMovimiento'])->middleware('permission:finanzas.movimientos.crear')->name('store');
            Route::get('/{movimiento}/detalles', [FinanzasController::class, 'showMovimiento'])->middleware('permission:finanzas.movimientos.detalles')->name('show');
            Route::get('/{movimiento}/editar', [FinanzasController::class, 'editMovimiento'])->middleware('permission:finanzas.movimientos.editar')->name('edit');
            Route::put('/{movimiento}', [FinanzasController::class, 'updateMovimiento'])->middleware('permission:finanzas.movimientos.editar')->name('update');
        });

        Route::get('/reportes', [FinanzasController::class, 'reportes'])->middleware('permission:finanzas.reportes.ver')->name('reportes');
        Route::get('/reportes/{tipo}/ver', [FinanzasController::class, 'verReporte'])->middleware('permission:finanzas.reportes.ver')->name('reportes.ver');
        Route::get('/reportes/{tipo}/excel', [FinanzasController::class, 'exportReporteExcel'])->middleware('permission:finanzas.reportes.ver')->name('reportes.excel');
        Route::get('/reportes/{tipo}/pdf', [FinanzasController::class, 'exportReportePdf'])->middleware('permission:finanzas.reportes.ver')->name('reportes.pdf');
    });

    Route::prefix('/empresa')->name('empresa.')->group(function () {
        Route::get('/preferencias', [EmpresaController::class, 'preferencias'])->name('preferencias');
        Route::patch('/preferencias', [EmpresaController::class, 'actualizarPreferencias'])->name('preferencias.update');
    });

    // Panel de superadministración (solo correos en config/superadmin.php).
    Route::middleware('superadmin')->prefix('/superadmin')->name('superadmin.')->group(function () {
        Route::get('/empresas', [SuperAdminController::class, 'empresas'])->name('empresas.index');
        Route::get('/empresas/{xnova}', [SuperAdminController::class, 'showEmpresa'])->name('empresas.show');
    });

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

<?php

use App\Http\Controllers\Finanzas\BolsilloController;
use App\Http\Controllers\Finanzas\CapturaRapidaController;
use App\Http\Controllers\Finanzas\CuentaController;
use App\Http\Controllers\Finanzas\CuentaPorCobrarController;
use App\Http\Controllers\Finanzas\DashboardController;
use App\Http\Controllers\Finanzas\DeudaController;
use App\Http\Controllers\Finanzas\GastoFijoController;
use App\Http\Controllers\Finanzas\IngresoFijoController;
use App\Http\Controllers\Finanzas\MovimientoController;
use App\Http\Controllers\Finanzas\PendienteController;
use App\Http\Controllers\Finanzas\PropietarioController;
use App\Http\Controllers\Finanzas\ReporteController;
use App\Http\Controllers\Finanzas\SubcuentaController;
use Illuminate\Support\Facades\Route;

// Se carga dentro del grupo ['auth', 'tenant'] de routes/web.php.
Route::prefix('/finanzas')->name('finanzas.')->group(function (): void {
    Route::get('/', DashboardController::class)->middleware('permission:finanzas.dashboard.ver')->name('dashboard');

    // Captura rápida (botón flotante global).
    Route::get('/quick/opciones', [CapturaRapidaController::class, 'opciones'])->middleware('permission:finanzas.movimientos.crear')->name('quick.opciones');
    Route::post('/quick', [CapturaRapidaController::class, 'store'])->middleware('permission:finanzas.movimientos.crear')->name('quick.store');

    Route::prefix('/pendientes')->name('pendientes.')->group(function (): void {
        Route::get('/', [PendienteController::class, 'index'])->middleware('permission:finanzas.movimientos.ver')->name('index');
        Route::post('/{movimiento}/confirmar', [PendienteController::class, 'confirmar'])->middleware('permission:finanzas.movimientos.crear')->name('confirmar');
        Route::post('/{movimiento}/anular', [PendienteController::class, 'anular'])->middleware('permission:finanzas.movimientos.crear')->name('anular');
    });

    Route::prefix('/propietarios')->name('propietarios.')->group(function (): void {
        Route::get('/', [PropietarioController::class, 'index'])->middleware('permission:finanzas.propietarios.ver')->name('index');
        Route::get('/data', [PropietarioController::class, 'data'])->middleware('permission:finanzas.propietarios.ver')->name('data');
        Route::get('/nuevo', [PropietarioController::class, 'create'])->middleware('permission:finanzas.propietarios.crear')->name('create');
        Route::post('/', [PropietarioController::class, 'store'])->middleware('permission:finanzas.propietarios.crear')->name('store');
        Route::get('/{propietario}/detalles', [PropietarioController::class, 'show'])->middleware('permission:finanzas.propietarios.detalles')->name('show');
        Route::get('/{propietario}/editar', [PropietarioController::class, 'edit'])->middleware('permission:finanzas.propietarios.editar')->name('edit');
        Route::put('/{propietario}', [PropietarioController::class, 'update'])->middleware('permission:finanzas.propietarios.editar')->name('update');
    });

    Route::prefix('/gastos-fijos')->name('gastos-fijos.')->group(function (): void {
        Route::get('/', [GastoFijoController::class, 'index'])->middleware('permission:finanzas.gastos-fijos.ver')->name('index');
        Route::get('/data', [GastoFijoController::class, 'data'])->middleware('permission:finanzas.gastos-fijos.ver')->name('data');
        Route::get('/nuevo', [GastoFijoController::class, 'create'])->middleware('permission:finanzas.gastos-fijos.crear')->name('create');
        Route::post('/', [GastoFijoController::class, 'store'])->middleware('permission:finanzas.gastos-fijos.crear')->name('store');
        Route::get('/{gasto}/detalles', [GastoFijoController::class, 'show'])->middleware('permission:finanzas.gastos-fijos.detalles')->name('show');
        Route::get('/{gasto}/editar', [GastoFijoController::class, 'edit'])->middleware('permission:finanzas.gastos-fijos.editar')->name('edit');
        Route::put('/{gasto}', [GastoFijoController::class, 'update'])->middleware('permission:finanzas.gastos-fijos.editar')->name('update');
    });

    Route::prefix('/cuentas')->name('cuentas.')->group(function (): void {
        Route::get('/', [CuentaController::class, 'index'])->middleware('permission:finanzas.cuentas.ver')->name('index');
        Route::get('/data', [CuentaController::class, 'data'])->middleware('permission:finanzas.cuentas.ver')->name('data');
        Route::get('/nuevo', [CuentaController::class, 'create'])->middleware('permission:finanzas.cuentas.crear')->name('create');
        Route::post('/', [CuentaController::class, 'store'])->middleware('permission:finanzas.cuentas.crear')->name('store');
        Route::get('/{cuenta}/detalles', [CuentaController::class, 'show'])->middleware('permission:finanzas.cuentas.detalles')->name('show');
        Route::get('/{cuenta}/editar', [CuentaController::class, 'edit'])->middleware('permission:finanzas.cuentas.editar')->name('edit');
        Route::put('/{cuenta}', [CuentaController::class, 'update'])->middleware('permission:finanzas.cuentas.editar')->name('update');
        Route::post('/{cuenta}/ajustar', [CuentaController::class, 'ajustar'])->middleware('permission:finanzas.movimientos.crear')->name('ajustar');

        Route::get('/{cuenta}/bolsillos/nuevo', [BolsilloController::class, 'create'])->middleware('permission:finanzas.bolsillos.crear')->name('bolsillos.create');
        Route::post('/{cuenta}/bolsillos', [BolsilloController::class, 'store'])->middleware('permission:finanzas.bolsillos.crear')->name('bolsillos.store');
        Route::get('/{cuenta}/bolsillos/{bolsillo}/editar', [BolsilloController::class, 'edit'])->middleware('permission:finanzas.bolsillos.editar')->name('bolsillos.edit');
        Route::put('/{cuenta}/bolsillos/{bolsillo}', [BolsilloController::class, 'update'])->middleware('permission:finanzas.bolsillos.editar')->name('bolsillos.update');
        Route::patch('/{cuenta}/bolsillos/{bolsillo}/desactivar', [BolsilloController::class, 'deactivate'])->middleware('permission:finanzas.bolsillos.desactivar')->name('bolsillos.deactivate');
        Route::post('/{cuenta}/distribuir', [BolsilloController::class, 'distribute'])->middleware('permission:finanzas.bolsillos.distribuir')->name('bolsillos.distribute');

        // Detalle de un bolsillo con acciones rápidas (ingreso, gasto, transferencia, pago de deuda).
        Route::get('/{cuenta}/subcuentas/{subcuenta}', [SubcuentaController::class, 'show'])->middleware('permission:finanzas.cuentas.detalles')->name('subcuentas.show');
        Route::post('/{cuenta}/subcuentas/{subcuenta}/ingreso', [SubcuentaController::class, 'ingreso'])->middleware('permission:finanzas.movimientos.crear')->name('subcuentas.ingreso');
        Route::post('/{cuenta}/subcuentas/{subcuenta}/gasto', [SubcuentaController::class, 'gasto'])->middleware('permission:finanzas.movimientos.crear')->name('subcuentas.gasto');
        Route::post('/{cuenta}/subcuentas/{subcuenta}/transferir', [SubcuentaController::class, 'transferir'])->middleware('permission:finanzas.movimientos.crear')->name('subcuentas.transferir');
        Route::post('/{cuenta}/subcuentas/{subcuenta}/pagar-deuda', [SubcuentaController::class, 'pagarDeuda'])->middleware('permission:finanzas.deudas.pagar')->name('subcuentas.pagar-deuda');
        Route::post('/{cuenta}/subcuentas/{subcuenta}/movimientos/{movimiento}/anular', [SubcuentaController::class, 'anular'])->middleware('permission:finanzas.movimientos.crear')->name('subcuentas.movimientos.anular');
    });

    Route::prefix('/deudas')->name('deudas.')->group(function (): void {
        Route::get('/', [DeudaController::class, 'index'])->middleware('permission:finanzas.deudas.ver')->name('index');
        Route::get('/data', [DeudaController::class, 'data'])->middleware('permission:finanzas.deudas.ver')->name('data');
        Route::get('/nuevo', [DeudaController::class, 'create'])->middleware('permission:finanzas.deudas.crear')->name('create');
        Route::post('/', [DeudaController::class, 'store'])->middleware('permission:finanzas.deudas.crear')->name('store');
        Route::get('/{deuda}/detalles', [DeudaController::class, 'show'])->middleware('permission:finanzas.deudas.detalles')->name('show');
        Route::get('/{deuda}/editar', [DeudaController::class, 'edit'])->middleware('permission:finanzas.deudas.editar')->name('edit');
        Route::put('/{deuda}', [DeudaController::class, 'update'])->middleware('permission:finanzas.deudas.editar')->name('update');
        Route::post('/{deuda}/pagar', [DeudaController::class, 'pagar'])->middleware('permission:finanzas.deudas.pagar')->name('pagar');
    });

    Route::prefix('/cuentas-por-cobrar')->name('cuentas-por-cobrar.')->group(function (): void {
        Route::get('/', [CuentaPorCobrarController::class, 'index'])->middleware('permission:finanzas.cuentas-por-cobrar.ver')->name('index');
        Route::get('/data', [CuentaPorCobrarController::class, 'data'])->middleware('permission:finanzas.cuentas-por-cobrar.ver')->name('data');
        Route::get('/nuevo', [CuentaPorCobrarController::class, 'create'])->middleware('permission:finanzas.cuentas-por-cobrar.crear')->name('create');
        Route::post('/', [CuentaPorCobrarController::class, 'store'])->middleware('permission:finanzas.cuentas-por-cobrar.crear')->name('store');
        Route::get('/{cuentaPorCobrar}/detalles', [CuentaPorCobrarController::class, 'show'])->middleware('permission:finanzas.cuentas-por-cobrar.detalles')->name('show');
        Route::get('/{cuentaPorCobrar}/editar', [CuentaPorCobrarController::class, 'edit'])->middleware('permission:finanzas.cuentas-por-cobrar.editar')->name('edit');
        Route::put('/{cuentaPorCobrar}', [CuentaPorCobrarController::class, 'update'])->middleware('permission:finanzas.cuentas-por-cobrar.editar')->name('update');
        Route::post('/{cuentaPorCobrar}/cobrar', [CuentaPorCobrarController::class, 'cobrar'])->middleware('permission:finanzas.cuentas-por-cobrar.cobrar')->name('cobrar');
    });

    Route::prefix('/ingresos-fijos')->name('ingresos-fijos.')->group(function (): void {
        Route::get('/', [IngresoFijoController::class, 'index'])->middleware('permission:finanzas.ingresos-fijos.ver')->name('index');
        Route::get('/data', [IngresoFijoController::class, 'data'])->middleware('permission:finanzas.ingresos-fijos.ver')->name('data');
        Route::get('/nuevo', [IngresoFijoController::class, 'create'])->middleware('permission:finanzas.ingresos-fijos.crear')->name('create');
        Route::post('/', [IngresoFijoController::class, 'store'])->middleware('permission:finanzas.ingresos-fijos.crear')->name('store');
        Route::get('/{ingreso}/detalles', [IngresoFijoController::class, 'show'])->middleware('permission:finanzas.ingresos-fijos.detalles')->name('show');
        Route::get('/{ingreso}/editar', [IngresoFijoController::class, 'edit'])->middleware('permission:finanzas.ingresos-fijos.editar')->name('edit');
        Route::put('/{ingreso}', [IngresoFijoController::class, 'update'])->middleware('permission:finanzas.ingresos-fijos.editar')->name('update');
    });

    Route::prefix('/movimientos')->name('movimientos.')->group(function (): void {
        Route::get('/', [MovimientoController::class, 'index'])->middleware('permission:finanzas.movimientos.ver')->name('index');
        Route::get('/data', [MovimientoController::class, 'data'])->middleware('permission:finanzas.movimientos.ver')->name('data');
        Route::get('/nuevo', [MovimientoController::class, 'create'])->middleware('permission:finanzas.movimientos.crear')->name('create');
        Route::post('/', [MovimientoController::class, 'store'])->middleware('permission:finanzas.movimientos.crear')->name('store');
        Route::get('/{movimiento}/detalles', [MovimientoController::class, 'show'])->middleware('permission:finanzas.movimientos.detalles')->name('show');
        Route::get('/{movimiento}/editar', [MovimientoController::class, 'edit'])->middleware('permission:finanzas.movimientos.editar')->name('edit');
        Route::put('/{movimiento}', [MovimientoController::class, 'update'])->middleware('permission:finanzas.movimientos.editar')->name('update');
    });

    Route::get('/reportes', [ReporteController::class, 'index'])->middleware('permission:finanzas.reportes.ver')->name('reportes');
    Route::get('/reportes/{tipo}/ver', [ReporteController::class, 'ver'])->middleware('permission:finanzas.reportes.ver')->name('reportes.ver');
    Route::get('/reportes/{tipo}/excel', [ReporteController::class, 'excel'])->middleware('permission:finanzas.reportes.ver')->name('reportes.excel');
    Route::get('/reportes/{tipo}/pdf', [ReporteController::class, 'pdf'])->middleware('permission:finanzas.reportes.ver')->name('reportes.pdf');
});

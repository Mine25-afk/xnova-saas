<?php

use App\Http\Controllers\Citas\CalendarioController;
use App\Http\Controllers\Citas\CitaController;
use App\Http\Controllers\Citas\CitaEstadoController;
use App\Http\Controllers\Citas\CitaReporteController;
use App\Http\Controllers\Citas\HuecosLibresController;
use Illuminate\Support\Facades\Route;

// Se carga dentro del grupo ['auth', 'tenant'] de routes/web.php.
Route::prefix('/citas')->name('citas.')->group(function (): void {
    Route::get('/', [CitaController::class, 'index'])->middleware('permission:citas.ver')->name('listado');
    Route::get('/data', [CitaController::class, 'data'])->middleware('permission:citas.ver')->name('data');
    Route::get('/huecos-libres', HuecosLibresController::class)->middleware('permission:citas.ver')->name('free-slots');
    Route::get('/nuevo', [CitaController::class, 'create'])->middleware('permission:citas.crear')->name('create');
    Route::post('/', [CitaController::class, 'store'])->middleware('permission:citas.crear')->name('store');
    Route::get('/calendario', CalendarioController::class)->middleware('permission:citas.calendario.ver')->name('calendario');
    Route::get('/reportes', CitaReporteController::class)->middleware('permission:citas.reportes.ver')->name('reportes');
    Route::get('/{cita}/detalles', [CitaController::class, 'show'])->middleware('permission:citas.detalles')->name('show');
    Route::get('/{cita}/editar', [CitaController::class, 'edit'])->middleware('permission:citas.editar')->name('edit');
    Route::put('/{cita}', [CitaController::class, 'update'])->middleware('permission:citas.editar')->name('update');
    Route::patch('/{cita}/terminar', [CitaEstadoController::class, 'terminar'])->middleware('permission:citas.terminar')->name('finish');
    Route::patch('/{cita}/cancelar', [CitaEstadoController::class, 'cancelar'])->middleware('permission:citas.cancelar')->name('cancel');
});

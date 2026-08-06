<?php

use App\Http\Controllers\AsistenciaController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartamentoController;
use App\Http\Controllers\EmpleadoController;
use App\Http\Controllers\NominaController;
use App\Http\Controllers\PermisoController;
use App\Http\Controllers\PuestoController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\UsuarioController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'));
Route::get('/login', [AuthController::class, 'showLogin'])->name('login')->middleware('guest');
Route::post('/login', [AuthController::class, 'login'])->name('login.post')->middleware('guest');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('empleados', EmpleadoController::class)->middleware('role:Administrador,Recursos Humanos,Empleado');
    Route::resource('departamentos', DepartamentoController::class)->middleware('role:Administrador,Recursos Humanos');
    Route::resource('puestos', PuestoController::class)->middleware('role:Administrador,Recursos Humanos');

    Route::resource('nominas', NominaController::class)->middleware('role:Administrador,Contador,Empleado');
    Route::patch('nominas/{nomina}/pagar', [NominaController::class, 'markPaid'])->name('nominas.pagar')->middleware('role:Administrador,Contador');
    Route::patch('nominas/{nomina}/cancelar', [NominaController::class, 'cancel'])->name('nominas.cancelar')->middleware('role:Administrador,Contador');

    Route::resource('asistencias', AsistenciaController::class)->middleware('role:Administrador,Recursos Humanos,Empleado');
    Route::resource('permisos', PermisoController::class)->middleware('role:Administrador,Recursos Humanos,Empleado');
    Route::patch('permisos/{permiso}/aprobar', [PermisoController::class, 'approve'])->name('permisos.aprobar')->middleware('role:Administrador,Recursos Humanos');
    Route::patch('permisos/{permiso}/rechazar', [PermisoController::class, 'reject'])->name('permisos.rechazar')->middleware('role:Administrador,Recursos Humanos');

    Route::get('reportes', [ReporteController::class, 'index'])->name('reportes.index')->middleware('role:Administrador,Contador');
    Route::get('reportes/empleados', [ReporteController::class, 'empleados'])->name('reportes.empleados')->middleware('role:Administrador,Contador');
    Route::get('reportes/nominas', [ReporteController::class, 'nominas'])->name('reportes.nominas')->middleware('role:Administrador,Contador');
    Route::get('reportes/asistencias', [ReporteController::class, 'asistencias'])->name('reportes.asistencias')->middleware('role:Administrador,Contador');
    Route::get('reportes/permisos', [ReporteController::class, 'permisos'])->name('reportes.permisos')->middleware('role:Administrador,Contador');
    Route::get('reportes/departamentos', [ReporteController::class, 'departamentos'])->name('reportes.departamentos')->middleware('role:Administrador,Contador');
    Route::get('reportes/pagos-pendientes', [ReporteController::class, 'pagosPendientes'])->name('reportes.pagos-pendientes')->middleware('role:Administrador,Contador');

    Route::resource('usuarios', UsuarioController::class)->except('show')->middleware('role:Administrador');
});

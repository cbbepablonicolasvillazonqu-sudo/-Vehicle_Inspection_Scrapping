<?php

use App\Http\Controllers\ExportController;
use App\Http\Controllers\ProfileController;
use App\Livewire\Admin\GestionUsuarios;
use App\Livewire\Admin\ReporteGanancias;
use App\Livewire\Dashboard;
use App\Livewire\Vehiculos\FichaVehiculo;
use App\Livewire\Vehiculos\FormularioVehiculo;
use App\Livewire\Vehiculos\ListaVehiculos;
use Illuminate\Support\Facades\Route;

// La raíz siempre lleva al panel (o al login si no hay sesión).
Route::redirect('/', '/panel');

// Página de respaldo del service worker cuando no hay conexión (PWA).
Route::view('/offline', 'offline')->name('offline');

// Cambio de idioma (es/en). Accesible con o sin sesión (también en el login).
Route::get('/idioma/{locale}', [\App\Http\Controllers\IdiomaController::class, 'cambiar'])
    ->name('idioma.cambiar');

Route::middleware('auth')->group(function () {
    // Panel principal (se conserva el nombre "dashboard" que usa Breeze
    // para las redirecciones posteriores al inicio de sesión).
    Route::get('/panel', Dashboard::class)->name('dashboard');

    // Perfil propio (cualquier usuario autenticado).
    Route::get('/perfil', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/perfil', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/perfil', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Gestión de usuarios y reportes: exclusivos del Administrador.
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/usuarios', GestionUsuarios::class)->name('usuarios.index');
    Route::get('/reportes/ganancias', ReporteGanancias::class)->name('reportes.ganancias');
    Route::get('/exportar/ganancias', [ExportController::class, 'ganancias'])->name('exportar.ganancias');
});

// Exportación de vehículos (XLSX/CSV) según permisos del rol.
Route::middleware(['auth', 'permission:exportar datos'])->group(function () {
    Route::get('/exportar/vehiculos/{formato}', [ExportController::class, 'vehiculos'])
        ->whereIn('formato', ['xlsx', 'csv'])
        ->name('exportar.vehiculos');
});

// Vehículos: todas las rutas exigen sesión + permiso "ver vehiculos";
// crear/editar además exigen rol admin o comprador. Las policies y los
// componentes Livewire validan de nuevo (defensa en profundidad).
Route::middleware(['auth', 'permission:ver vehiculos'])->group(function () {
    Route::get('/vehiculos', ListaVehiculos::class)->name('vehiculos.index');

    Route::middleware('role:admin|comprador')->group(function () {
        Route::get('/vehiculos/crear', FormularioVehiculo::class)->name('vehiculos.crear');
        Route::get('/vehiculos/{vehiculo}/editar', FormularioVehiculo::class)->name('vehiculos.editar');
    });

    Route::get('/vehiculos/{vehiculo}', FichaVehiculo::class)->name('vehiculos.ficha');
});

require __DIR__.'/auth.php';

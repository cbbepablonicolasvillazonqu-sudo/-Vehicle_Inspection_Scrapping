<?php

use App\Http\Controllers\ProfileController;
use App\Livewire\Admin\GestionUsuarios;
use Illuminate\Support\Facades\Route;

// La raíz siempre lleva al panel (o al login si no hay sesión).
Route::redirect('/', '/panel');

Route::middleware('auth')->group(function () {
    // Panel principal (se conserva el nombre "dashboard" que usa Breeze
    // para las redirecciones posteriores al inicio de sesión).
    Route::get('/panel', function () {
        return view('dashboard');
    })->name('dashboard');

    // Perfil propio (cualquier usuario autenticado).
    Route::get('/perfil', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/perfil', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/perfil', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Gestión de usuarios: exclusiva del Administrador.
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/usuarios', GestionUsuarios::class)->name('usuarios.index');
});

require __DIR__.'/auth.php';

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * El VIN deja de ser único: el mismo auto puede volver a entrar (se vendió y
 * se recompró, o se cargó, se borró y se vuelve a cargar). La app ya no lo
 * bloquea: avisa con un enlace a los otros vehículos que tienen ese VIN.
 *
 * Queda un índice normal para que la búsqueda y el aviso sigan siendo rápidos.
 *
 * Volver atrás falla si ya hay VIN repetidos, y es a propósito: primero hay
 * que decidir qué hacer con los duplicados, el índice no puede elegir solo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $tabla) {
            $tabla->dropUnique('vehicles_vin_unique');
            $tabla->index('vin');
        });
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $tabla) {
            $tabla->dropIndex('vehicles_vin_index');
            $tabla->unique('vin');
        });
    }
};

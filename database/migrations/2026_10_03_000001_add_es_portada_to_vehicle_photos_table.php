<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Las fotos del vehículo pasan a ser una galería: cualquier usuario puede
 * sumar fotos. Hace falta guardar cuál es la portada, porque "la más nueva"
 * dejaría como portada la última foto de un rayón.
 *
 * Sin portada marcada, la portada es la primera foto subida. Para los
 * vehículos que ya tienen su única foto, nada cambia.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicle_photos', function (Blueprint $tabla) {
            $tabla->boolean('es_portada')->default(false)->after('etapa');
        });
    }

    public function down(): void
    {
        Schema::table('vehicle_photos', function (Blueprint $tabla) {
            $tabla->dropColumn('es_portada');
        });
    }
};

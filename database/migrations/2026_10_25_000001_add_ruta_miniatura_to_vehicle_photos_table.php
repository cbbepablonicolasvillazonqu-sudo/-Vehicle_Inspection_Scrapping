<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Miniatura de cada foto, para las listas y las galerías.
 *
 * Las fotos de celular llegan de 4032x3024 y unos 4 MB, y se mostraban así
 * en tarjetas de 112 px: una página de la lista bajaba 46 MB. Ahora la foto
 * se guarda reducida (WebP, 2560 px) y además una miniatura de unos 20 KB.
 *
 * Null en las fotos que todavía no se optimizaron: la app muestra la foto
 * entera, como antes, hasta que se corra `php artisan forte:optimizar-fotos`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicle_photos', function (Blueprint $tabla) {
            $tabla->string('ruta_miniatura')->nullable()->after('ruta');
        });
    }

    public function down(): void
    {
        Schema::table('vehicle_photos', function (Blueprint $tabla) {
            $tabla->dropColumn('ruta_miniatura');
        });
    }
};

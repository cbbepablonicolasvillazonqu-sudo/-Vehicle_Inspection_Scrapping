<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * La asignación de recojo solo captura marca, modelo, año, VIN y ubicación.
     * El resto de datos se completan más tarde (el Gruero al recoger, el Admin
     * al cerrar la compra), así que dejan de ser obligatorios en la base.
     *
     * No borra ni transforma datos: solo permite NULL en filas futuras.
     */
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->unsignedInteger('millas')->nullable()->change();
            $table->decimal('precio_compra', 10, 2)->nullable()->change();
            $table->date('fecha_compra')->nullable()->change();
            $table->string('lugar_compra', 20)->nullable()->change();
            $table->string('estado_titulo', 20)->nullable()->change();
        });
    }

    public function down(): void
    {
        // Solo es reversible si no quedan filas con NULL en esas columnas.
        Schema::table('vehicles', function (Blueprint $table) {
            $table->unsignedInteger('millas')->nullable(false)->change();
            $table->decimal('precio_compra', 10, 2)->nullable(false)->change();
            $table->date('fecha_compra')->nullable(false)->change();
            $table->string('lugar_compra', 20)->nullable(false)->change();
            $table->string('estado_titulo', 20)->nullable(false)->change();
        });
    }
};

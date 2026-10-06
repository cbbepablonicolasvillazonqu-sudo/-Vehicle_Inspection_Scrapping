<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Documentos del vehículo (título, registro, etc.), como foto o PDF.
 *
 * Van en su propia tabla y no en vehicle_photos a propósito: allí todo lo que
 * no tiene gasto se trata como foto del vehículo, y la migración vieja
 * 2026_07_23_000006 borra esas filas si alguien la vuelve a ejecutar. Los
 * documentos no pueden quedar expuestos a eso.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_documents', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $tabla->string('ruta');
            $tabla->string('nombre_original')->nullable();
            $tabla->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $tabla->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_documents');
    }
};

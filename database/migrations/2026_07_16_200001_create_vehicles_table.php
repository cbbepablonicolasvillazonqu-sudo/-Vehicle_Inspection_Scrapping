<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('marca', 60);
            $table->string('modelo', 60);
            $table->unsignedSmallInteger('anio');
            $table->char('vin', 17)->unique();
            $table->unsignedInteger('millas');
            $table->decimal('precio_compra', 10, 2);
            $table->date('fecha_compra');
            // Se guardan como string + enum PHP (no ENUM de SQL) para poder
            // agregar valores sin ALTER TABLE y mantener compatibilidad MariaDB.
            $table->string('lugar_compra', 20);
            $table->string('estado_titulo', 20);
            $table->string('estado', 20)->default('comprado')->index();
            $table->text('notas')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['marca', 'modelo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};

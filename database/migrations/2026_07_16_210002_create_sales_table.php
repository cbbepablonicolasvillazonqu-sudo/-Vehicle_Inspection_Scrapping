<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Venta del vehículo (una por vehículo). Al registrarla, el vehículo
        // pasa a "Vendido" y queda bloqueado para todos excepto Admin.
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->unique()->constrained()->cascadeOnDelete();
            $table->date('fecha_venta')->index();
            $table->decimal('precio_venta', 10, 2);
            $table->string('nombre_comprador', 120);
            $table->string('telefono_comprador', 30);
            $table->string('metodo_pago', 30);
            $table->text('notas')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};

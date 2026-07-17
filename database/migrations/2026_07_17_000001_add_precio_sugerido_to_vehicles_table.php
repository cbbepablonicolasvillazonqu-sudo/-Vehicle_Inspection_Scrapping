<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            // Precio de venta sugerido (lo fija el Admin). Es la referencia
            // del Vendedor para negociar sin exponerle compra ni gastos.
            $table->decimal('precio_sugerido', 10, 2)->nullable()->after('precio_compra');
        });
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn('precio_sugerido');
        });
    }
};

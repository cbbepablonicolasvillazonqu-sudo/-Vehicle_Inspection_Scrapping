<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Las fotos pasan a colgar de un gasto (único módulo con carga de fotos).
     * Se conserva la columna "etapa" por compatibilidad histórica, pero las
     * fotos nuevas siempre llevan expense_id.
     */
    public function up(): void
    {
        Schema::table('vehicle_photos', function (Blueprint $table) {
            $table->foreignId('expense_id')->nullable()->after('vehicle_id')
                ->constrained('expenses')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('vehicle_photos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('expense_id');
        });
    }
};

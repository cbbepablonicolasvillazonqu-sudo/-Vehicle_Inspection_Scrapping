<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            // Ubicaciones: el origen lo carga el Admin como enlace de Google Maps;
            // el destino lo elige el Gruero de una lista fija (ver enum UbicacionDestino).
            $table->string('ubicacion_origen_url', 2048)->nullable()->after('notas');
            $table->string('ubicacion_destino', 20)->nullable()->after('ubicacion_origen_url');

            // Recojo asignado: el Gruero solo ve los vehículos que apuntan a él.
            $table->foreignId('asignado_a')->nullable()->after('created_by')
                ->constrained('users')->nullOnDelete();

            // Datos que registra el Gruero al recoger el vehículo.
            $table->string('metodo_pago_gruero', 20)->nullable()->after('asignado_a');
            $table->boolean('tiene_catalizador')->nullable()->after('metodo_pago_gruero');
            $table->decimal('monto_pagado', 10, 2)->nullable()->after('tiene_catalizador');

            $table->index('asignado_a', 'vehicles_asignado_a_index');
        });
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropIndex('vehicles_asignado_a_index');
            $table->dropConstrainedForeignId('asignado_a');
            $table->dropColumn([
                'ubicacion_origen_url',
                'ubicacion_destino',
                'metodo_pago_gruero',
                'tiene_catalizador',
                'monto_pagado',
            ]);
        });
    }
};

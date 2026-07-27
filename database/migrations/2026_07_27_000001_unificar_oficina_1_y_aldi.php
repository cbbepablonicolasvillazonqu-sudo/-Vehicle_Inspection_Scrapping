<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * "OFICINA 1" y "ALDI" pasan a ser un único lugar: "OFICINA 1 ALDI".
     * Se reasignan los registros previos para que el enum de PHP no falle.
     */
    public function up(): void
    {
        DB::table('vehicles')
            ->whereIn('ubicacion_destino', ['oficina_1', 'aldi'])
            ->update(['ubicacion_destino' => 'oficina_1_aldi']);
    }

    public function down(): void
    {
        DB::table('vehicles')
            ->where('ubicacion_destino', 'oficina_1_aldi')
            ->update(['ubicacion_destino' => 'oficina_1']);
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * El campo "Title status" pasa a: Clean, Rebuild y Salvage.
     *
     * Los valores retirados se reasignan para que las fichas existentes sigan
     * abriendo (el enum de PHP fallaría con un valor desconocido):
     *   en_mano   -> clean
     *   pendiente -> clean
     *   salvage   -> se conserva
     */
    public function up(): void
    {
        DB::table('vehicles')
            ->whereIn('estado_titulo', ['en_mano', 'pendiente'])
            ->update(['estado_titulo' => 'clean']);
    }

    public function down(): void
    {
        // El valor original (en_mano / pendiente) no se puede reconstruir:
        // se deja "en_mano" como aproximación para poder revertir el esquema.
        DB::table('vehicles')
            ->where('estado_titulo', 'clean')
            ->update(['estado_titulo' => 'en_mano']);
    }
};

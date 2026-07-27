<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    /**
     * Las fotos ahora solo existen dentro del módulo de Gastos. Las cargadas
     * en las etapas retiradas (compra / reparación / venta) quedarían huérfanas,
     * así que se eliminan junto con su archivo en disco.
     */
    public function up(): void
    {
        $huerfanas = DB::table('vehicle_photos')->whereNull('expense_id')->get(['id', 'ruta']);

        foreach ($huerfanas as $foto) {
            Storage::disk('public')->delete($foto->ruta);
        }

        DB::table('vehicle_photos')->whereNull('expense_id')->delete();
    }

    public function down(): void
    {
        // Los archivos ya se borraron del disco: no hay vuelta atrás.
    }
};

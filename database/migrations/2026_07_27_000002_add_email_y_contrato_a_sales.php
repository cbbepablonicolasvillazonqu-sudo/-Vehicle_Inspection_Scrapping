<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->string('email_comprador', 150)->nullable()->after('telefono_comprador');
            // Contrato firmado: imagen o PDF en storage/app/public/ventas/.
            $table->string('contrato_ruta')->nullable()->after('metodo_pago');
            $table->string('contrato_nombre')->nullable()->after('contrato_ruta');
        });

        // "Transferencia" se reemplaza por "Zelle" como forma de pago.
        DB::table('sales')
            ->where('metodo_pago', 'transferencia')
            ->update(['metodo_pago' => 'zelle']);
    }

    public function down(): void
    {
        DB::table('sales')
            ->where('metodo_pago', 'zelle')
            ->update(['metodo_pago' => 'transferencia']);

        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['email_comprador', 'contrato_ruta', 'contrato_nombre']);
        });
    }
};

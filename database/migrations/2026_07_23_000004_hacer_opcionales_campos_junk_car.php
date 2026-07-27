<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * El envío masivo a Junk car marca varios vehículos de una vez; el monto
     * recibido y la empresa se completan después, uno por uno. Por eso dejan
     * de ser obligatorios (los registros existentes conservan sus valores).
     */
    public function up(): void
    {
        Schema::table('scrap_records', function (Blueprint $table) {
            $table->decimal('monto_recibido', 10, 2)->nullable()->change();
            $table->string('empresa', 120)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('scrap_records', function (Blueprint $table) {
            $table->decimal('monto_recibido', 10, 2)->nullable(false)->change();
            $table->string('empresa', 120)->nullable(false)->change();
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * El cliente pidió que ninguna casilla del formulario de venta sea obligatoria:
 * a veces se cierra el trato y los datos llegan después.
 *
 * Las cinco columnas del negocio eran NOT NULL sin valor por defecto, así que
 * relajar la validación sin tocar el esquema fallaba con "Field doesn't have a
 * default value". No se toca ninguna fila: las ventas ya cargadas están completas.
 *
 * Ojo: en MySQL, change() reescribe la definición entera de la columna, por eso
 * hay que repetir el tipo exacto y no solo el nullable.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $tabla) {
            $tabla->date('fecha_venta')->nullable()->change();
            $tabla->decimal('precio_venta', 10, 2)->nullable()->change();
            $tabla->string('nombre_comprador', 120)->nullable()->change();
            $tabla->string('telefono_comprador', 30)->nullable()->change();
            $tabla->string('metodo_pago', 30)->nullable()->change();
        });
    }

    public function down(): void
    {
        // Volver a NOT NULL fallaría si ya hay ventas incompletas, que es
        // justamente lo que este cambio habilita. Se completan a mano primero.
        Schema::table('sales', function (Blueprint $tabla) {
            $tabla->date('fecha_venta')->nullable(false)->change();
            $tabla->decimal('precio_venta', 10, 2)->nullable(false)->change();
            $tabla->string('nombre_comprador', 120)->nullable(false)->change();
            $tabla->string('telefono_comprador', 30)->nullable(false)->change();
            $tabla->string('metodo_pago', 30)->nullable(false)->change();
        });
    }
};

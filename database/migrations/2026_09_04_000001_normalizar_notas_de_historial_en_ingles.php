<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Las notas que genera el sistema se guardan siempre en español y se traducen
 * al mostrarlas (la nota es la clave de traducción). Antes, el envío masivo a
 * Junk car traducía al escribir, así que las filas creadas por un usuario con
 * la interfaz en inglés quedaron con el texto ya traducido y ninguna clave que
 * las alcance. Se devuelven a la forma canónica.
 *
 * Solo toca notas generadas por el sistema: las que escribe una persona a mano
 * nunca coinciden con estos textos exactos.
 */
return new class extends Migration
{
    /** Texto guardado en inglés => clave canónica en español. */
    private const EQUIVALENCIAS = [
        'Bulk send to Junk car' => 'Envío masivo a Junk car',
    ];

    public function up(): void
    {
        foreach (self::EQUIVALENCIAS as $ingles => $espanol) {
            DB::table('vehicle_status_histories')
                ->where('nota', $ingles)
                ->update(['nota' => $espanol]);
        }
    }

    public function down(): void
    {
        // Sin vuelta atrás: el idioma original de cada fila no se puede saber,
        // y la forma canónica es la correcta en ambos idiomas.
    }
};

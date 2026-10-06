<?php

namespace App\Console\Commands;

use App\Services\ServicioArchivos;
use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

/**
 * Mueve los archivos ya subidos del disco "public" (/storage, abierto a
 * cualquiera con el enlace) al disco "privado".
 *
 * No toca la base de datos: la ruta guardada es la misma en los dos discos,
 * y ArchivoController busca en ambos, así que la app sigue mostrando todo
 * antes, durante y después de correrlo.
 *
 * Sin --ejecutar solo muestra qué movería. Se puede correr varias veces: lo
 * que ya está en el disco privado se saltea.
 */
class ProtegerArchivos extends Command
{
    protected $signature = 'forte:proteger-archivos
        {--ejecutar : Mover de verdad (sin esto solo muestra qué movería)}';

    protected $description = 'Mueve fotos, documentos y contratos ya subidos de /storage al disco privado';

    /** Carpetas con archivos de los usuarios. Nada más del disco public se toca. */
    private const CARPETAS = ['vehiculos', 'ventas'];

    public function handle(): int
    {
        $origen = Storage::disk(ServicioArchivos::DISCO_ANTERIOR);
        $destino = Storage::disk(ServicioArchivos::DISCO);
        $ejecutar = (bool) $this->option('ejecutar');

        $archivos = collect(self::CARPETAS)
            ->flatMap(fn (string $carpeta) => $origen->allFiles($carpeta))
            ->values();

        if ($archivos->isEmpty()) {
            $this->info('No hay archivos en /storage para proteger.');

            return self::SUCCESS;
        }

        $movidos = 0;
        $yaEstaban = 0;
        $conflictos = [];

        foreach ($archivos as $ruta) {
            if ($destino->exists($ruta)) {
                // Misma ruta y mismo tamaño: es la copia de una corrida anterior
                // que se cortó antes de borrar el original.
                if ($destino->size($ruta) === $origen->size($ruta)) {
                    if ($ejecutar) {
                        $origen->delete($ruta);
                    }

                    $yaEstaban++;
                } else {
                    $conflictos[] = $ruta;
                }

                continue;
            }

            if ($ejecutar) {
                $flujo = $origen->readStream($ruta);
                $destino->writeStream($ruta, $flujo);

                if (is_resource($flujo)) {
                    fclose($flujo);
                }

                // Solo se borra el original si la copia quedó completa.
                if ($destino->size($ruta) !== $origen->size($ruta)) {
                    $destino->delete($ruta);
                    $conflictos[] = $ruta;

                    continue;
                }

                $origen->delete($ruta);
            }

            $movidos++;
        }

        if ($ejecutar) {
            $this->limpiarCarpetasVacias($origen);
        }

        $this->line(($ejecutar ? 'Movidos: ' : 'Se moverían: ').$movidos);
        $this->line(($ejecutar ? 'Ya estaban en el disco privado (original borrado): ' : 'Ya están en el disco privado: ').$yaEstaban);

        if ($conflictos !== []) {
            $this->warn('Sin tocar, porque ya existe otro archivo distinto con la misma ruta o la copia falló: '.count($conflictos));

            foreach ($conflictos as $ruta) {
                $this->line("  {$ruta}");
            }
        }

        if (! $ejecutar) {
            $this->newLine();
            $this->info('No se movió nada. Para hacerlo: php artisan forte:proteger-archivos --ejecutar');
        }

        return $conflictos === [] ? self::SUCCESS : self::FAILURE;
    }

    /** Borra las carpetas que quedaron vacías; si queda algún archivo, no se tocan. */
    private function limpiarCarpetasVacias(Filesystem $origen): void
    {
        foreach (self::CARPETAS as $carpeta) {
            if ($origen->exists($carpeta) && $origen->allFiles($carpeta) === []) {
                $origen->deleteDirectory($carpeta);
            }
        }
    }
}

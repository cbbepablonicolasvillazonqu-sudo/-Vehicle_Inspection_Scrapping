<?php

namespace App\Console\Commands;

use App\Models\VehiclePhoto;
use App\Services\ServicioArchivos;
use App\Services\ServicioFotos;
use Illuminate\Console\Command;

/**
 * Optimiza las fotos que se subieron antes de que existieran las miniaturas:
 * las guarda reducidas en WebP, les hace la miniatura y borra el original.
 *
 * Sin --ejecutar solo muestra cuántas hay y cuánto pesan. Se puede correr
 * varias veces: las fotos que ya tienen miniatura se saltean. Si se corta a
 * mitad, la foto en curso queda como estaba: primero se escribe lo nuevo,
 * después se actualiza la base y recién al final se borra el original.
 *
 * Borrar el original no se puede deshacer: antes de correrlo en producción
 * conviene un respaldo de storage/app/privado.
 */
class OptimizarFotos extends Command
{
    protected $signature = 'forte:optimizar-fotos
        {--ejecutar : Optimizar de verdad (sin esto solo muestra qué haría)}';

    protected $description = 'Reduce a WebP las fotos ya subidas, les hace miniatura y borra el original';

    public function handle(ServicioArchivos $archivos, ServicioFotos $fotos): int
    {
        $ejecutar = (bool) $this->option('ejecutar');
        $pendientes = VehiclePhoto::query()->whereNull('ruta_miniatura');
        $total = (clone $pendientes)->count();

        if ($total === 0) {
            $this->info('Todas las fotos ya están optimizadas.');

            return self::SUCCESS;
        }

        $antes = 0;
        $despues = 0;
        $optimizadas = 0;
        $faltan = [];
        $fallidas = [];
        $barra = $this->output->createProgressBar($total);

        // chunkById y no chunk: cada foto optimizada deja de cumplir el
        // whereNull, y chunk se saltearía la mitad de las páginas.
        $pendientes->chunkById(50, function ($lote) use (
            $archivos, $fotos, $ejecutar, $barra, &$antes, &$despues, &$optimizadas, &$faltan, &$fallidas
        ) {
            foreach ($lote as $foto) {
                $barra->advance();
                $disco = $archivos->discoDe($foto->ruta);

                if ($disco === null) {
                    $faltan[] = $foto->ruta;

                    continue;
                }

                if (! $ejecutar) {
                    $antes += $disco->size($foto->ruta);

                    continue;
                }

                $contenido = $disco->get($foto->ruta);
                $versiones = $fotos->procesar($contenido);

                if ($versiones === null) {
                    $fallidas[] = $foto->ruta;

                    continue;
                }

                $rutaVieja = $foto->ruta;
                $foto->forceFill($fotos->guardarVersiones($versiones, dirname($rutaVieja)))->save();
                $archivos->borrar($rutaVieja);

                $antes += strlen($contenido);
                $despues += strlen($versiones['foto']) + strlen($versiones['miniatura']);
                $optimizadas++;
            }
        });

        $barra->finish();
        $this->newLine(2);

        $mb = fn (int $bytes) => number_format($bytes / 1048576, 1).' MB';

        if ($ejecutar) {
            $this->line("Optimizadas: {$optimizadas}");
            $this->line("Espacio: {$mb($antes)} antes, {$mb($despues)} después (fotos y miniaturas)");
        } else {
            $this->line('Se optimizarían: '.($total - count($faltan))." (hoy pesan {$mb($antes)})");
        }

        if ($faltan !== []) {
            $this->warn('No están en ningún disco, se dejaron como están: '.count($faltan));

            foreach ($faltan as $ruta) {
                $this->line("  {$ruta}");
            }
        }

        if ($fallidas !== []) {
            $this->warn('No se pudieron procesar y quedan enteras, como antes: '.count($fallidas));

            foreach ($fallidas as $ruta) {
                $this->line("  {$ruta}");
            }
        }

        if (! $ejecutar) {
            $this->newLine();
            $this->info('No se tocó nada. Para hacerlo: php artisan forte:optimizar-fotos --ejecutar');
            $this->warn('Borra los originales: antes hacé un respaldo de storage/app/privado.');
        }

        return self::SUCCESS;
    }
}

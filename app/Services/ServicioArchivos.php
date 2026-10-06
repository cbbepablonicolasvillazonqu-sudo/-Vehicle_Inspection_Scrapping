<?php

namespace App\Services;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Dónde viven los archivos que suben los usuarios: fotos del vehículo,
 * documentos, fotos de gastos y contratos.
 *
 * Van al disco "privado", que el servidor web no entrega: solo se ven a través
 * de ArchivoController, que exige sesión y permiso. Antes iban al disco
 * "public" (/storage), donde cualquiera con el enlace los abría sin cuenta.
 *
 * Los archivos viejos siguen en "public" hasta que se corre
 * `php artisan forte:proteger-archivos`. Por eso buscar y borrar miran los dos
 * discos: el código funciona igual antes y después de mover los archivos.
 */
class ServicioArchivos
{
    public const DISCO = 'privado';

    /** Donde estaban los archivos antes; puede quedar alguno sin mover. */
    public const DISCO_ANTERIOR = 'public';

    /** Guarda el archivo subido y devuelve la ruta para la base de datos. */
    public function guardar(UploadedFile $archivo, string $carpeta): string
    {
        return $archivo->store($carpeta, self::DISCO);
    }

    /** Disco en el que está el archivo, o null si no está en ninguno. */
    public function discoDe(string $ruta): ?Filesystem
    {
        foreach ([self::DISCO, self::DISCO_ANTERIOR] as $nombre) {
            if (Storage::disk($nombre)->exists($ruta)) {
                return Storage::disk($nombre);
            }
        }

        return null;
    }

    /** Borra el archivo de los dos discos, para no dejar una copia olvidada. */
    public function borrar(?string $ruta): void
    {
        if (blank($ruta)) {
            return;
        }

        Storage::disk(self::DISCO)->delete($ruta);
        Storage::disk(self::DISCO_ANTERIOR)->delete($ruta);
    }
}

<?php

namespace App\Services;

use App\Models\VehiclePhoto;
use GdImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Reduce las fotos que se suben y les hace una miniatura.
 *
 * Una foto de celular llega de 4032x3024 y unos 4 MB, y se mostraba entera en
 * tarjetas de 112 px: una página de la lista bajaba 46 MB, tardaba 45 s en 4G
 * y el scroll se trababa, porque cada foto ocupa 48 MB de memoria una vez
 * decodificada.
 *
 * Se guardan dos versiones en WebP y el original no se conserva (lo decidió
 * el cliente):
 *  - la foto, con el lado mayor en 2560 px: alcanza para hacer zoom y leer un
 *    recibo o un VIN, y pesa un 80 % menos;
 *  - la miniatura, con el lado menor en 480 px (unos 20 KB): llena sin
 *    pixelarse las tarjetas y la galería en la pantalla de un celular.
 *
 * Si la foto no se puede procesar (un formato que GD no lee, o tan grande que
 * no entra en la memoria de PHP), se guarda tal como llegó y sin miniatura, y
 * la app la muestra entera, como antes. Nunca se pierde una foto por esto.
 */
class ServicioFotos
{
    public const LADO_MAYOR = 2560;

    public const LADO_MENOR_MINIATURA = 480;

    private const CALIDAD = 80;

    private const CALIDAD_MINIATURA = 75;

    public function __construct(private ServicioArchivos $archivos) {}

    /**
     * Guarda la foto subida en la carpeta y devuelve las columnas para la base:
     * ['ruta' => ..., 'ruta_miniatura' => ... o null].
     */
    public function guardar(UploadedFile $foto, string $carpeta): array
    {
        $versiones = $this->procesar($foto->get());

        if ($versiones === null) {
            return ['ruta' => $this->archivos->guardar($foto, $carpeta), 'ruta_miniatura' => null];
        }

        return $this->guardarVersiones($versiones, $carpeta);
    }

    /**
     * Escribe las dos versiones en el disco privado, con un nombre al azar como
     * el que pone Laravel al subir. La miniatura va en la subcarpeta "mini".
     */
    public function guardarVersiones(array $versiones, string $carpeta): array
    {
        $nombre = Str::random(40).'.webp';
        $columnas = ['ruta' => "{$carpeta}/{$nombre}", 'ruta_miniatura' => "{$carpeta}/mini/{$nombre}"];

        Storage::disk(ServicioArchivos::DISCO)->put($columnas['ruta'], $versiones['foto']);
        Storage::disk(ServicioArchivos::DISCO)->put($columnas['ruta_miniatura'], $versiones['miniatura']);

        return $columnas;
    }

    /** Borra la foto y su miniatura. */
    public function borrar(VehiclePhoto $foto): void
    {
        $this->archivos->borrar($foto->ruta);
        $this->archivos->borrar($foto->ruta_miniatura);
    }

    /**
     * Las dos versiones WebP de una imagen, ['foto' => bytes, 'miniatura' => bytes],
     * o null si no se puede procesar.
     */
    public function procesar(string $contenido): ?array
    {
        try {
            $medidas = getimagesizefromstring($contenido);

            if ($medidas === false || ! $this->alcanzaLaMemoria($medidas[0], $medidas[1])) {
                return null;
            }

            $imagen = imagecreatefromstring($contenido);

            if (! imageistruecolor($imagen)) {
                imagepalettetotruecolor($imagen); // GIF y PNG de paleta: WebP no los acepta así
            }

            // Primero se achica y después se gira: girar la foto entera
            // duplicaría en memoria una imagen de 50 MB.
            $foto = $this->enderezar(
                $this->achicar($imagen, self::LADO_MAYOR / max(imagesx($imagen), imagesy($imagen))),
                $this->orientacionExif($contenido),
            );
            unset($imagen);

            $miniatura = $this->achicar($foto, self::LADO_MENOR_MINIATURA / min(imagesx($foto), imagesy($foto)));

            return [
                'foto' => $this->webp($foto, self::CALIDAD),
                'miniatura' => $this->webp($miniatura, self::CALIDAD_MINIATURA),
            ];
        } catch (\Throwable $e) {
            // Clave y no frase, como las acciones de la auditoría: es para quien
            // lea el log, no para el usuario. La foto se guarda tal como llegó.
            Log::warning('foto_no_optimizada', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * ¿Entra la imagen en la memoria de PHP? GD la descomprime entera: una de
     * 12 MP ocupa unos 50 MB y una de 50 MP, unos 200 MB. El margen cubre la
     * copia achicada, la girada y la miniatura.
     */
    private function alcanzaLaMemoria(int $ancho, int $alto): bool
    {
        $limite = $this->aBytes((string) ini_get('memory_limit'));

        if ($limite <= 0) {
            return true; // -1: sin límite
        }

        return memory_get_usage() + $ancho * $alto * 5 + 48 * 1024 * 1024 <= $limite;
    }

    private function aBytes(string $valor): int
    {
        $numero = (int) $valor;

        return match (strtolower(substr(trim($valor), -1))) {
            'g' => $numero * 1024 ** 3,
            'm' => $numero * 1024 ** 2,
            'k' => $numero * 1024,
            default => $numero,
        };
    }

    /** Escala por el factor dado. Nunca agranda: una foto chica queda como está. */
    private function achicar(GdImage $imagen, float $factor): GdImage
    {
        if ($factor >= 1) {
            return $imagen;
        }

        $ancho = max(1, (int) round(imagesx($imagen) * $factor));
        $alto = max(1, (int) round(imagesy($imagen) * $factor));

        $nueva = imagecreatetruecolor($ancho, $alto);
        imagealphablending($nueva, false);
        imagesavealpha($nueva, true); // un PNG con transparencia la conserva
        imagecopyresampled($nueva, $imagen, 0, 0, 0, 0, $ancho, $alto, imagesx($imagen), imagesy($imagen));

        return $nueva;
    }

    /**
     * Gira la imagen según la orientación EXIF (la misma tabla que usan las
     * librerías de imágenes; imagerotate gira en sentido antihorario).
     */
    private function enderezar(GdImage $imagen, int $orientacion): GdImage
    {
        $girada = match ($orientacion) {
            3, 4 => imagerotate($imagen, 180, 0),
            5, 6 => imagerotate($imagen, 270, 0),
            7, 8 => imagerotate($imagen, 90, 0),
            default => $imagen,
        };

        if (in_array($orientacion, [2, 4, 5, 7], true)) {
            imageflip($girada, IMG_FLIP_HORIZONTAL);
        }

        return $girada;
    }

    /**
     * Orientación EXIF de un JPEG: de 1 a 8, donde 1 es "ya está derecha".
     *
     * El celular guarda la foto como sale del sensor y anota aparte cómo
     * girarla. El navegador respeta esa nota al mostrar el original, pero GD
     * no: sin esto, las fotos verticales quedarían acostadas. Se lee a mano
     * para no depender de la extensión exif de PHP, que no siempre está.
     */
    private function orientacionExif(string $contenido): int
    {
        try {
            if (! str_starts_with($contenido, "\xFF\xD8")) {
                return 1; // no es JPEG
            }

            $pos = 2;

            while ($pos + 4 <= strlen($contenido) && $contenido[$pos] === "\xFF") {
                $marcador = ord($contenido[$pos + 1]);
                $largo = unpack('n', $contenido, $pos + 2)[1];

                if ($marcador === 0xE1 && substr($contenido, $pos + 4, 6) === "Exif\0\0") {
                    return $this->orientacionTiff(substr($contenido, $pos + 10, $largo - 8));
                }

                if ($marcador === 0xDA) {
                    break; // empiezan los datos de la imagen: ya no hay EXIF
                }

                $pos += 2 + $largo;
            }
        } catch (\Throwable) {
            // EXIF dañado: se deja la foto como viene.
        }

        return 1;
    }

    /** Busca la etiqueta 0x0112 (orientación) en el primer directorio TIFF. */
    private function orientacionTiff(string $tiff): int
    {
        $intel = str_starts_with($tiff, 'II');

        $leer = function (int $pos, int $bytes) use ($tiff, $intel): int {
            if ($pos < 0 || $pos + $bytes > strlen($tiff)) {
                throw new \RangeException; // EXIF incompleto: orientacionExif() lo ataja
            }

            $formato = $bytes === 2 ? ($intel ? 'v' : 'n') : ($intel ? 'V' : 'N');

            return unpack($formato, $tiff, $pos)[1];
        };

        $directorio = $leer(4, 4);

        for ($i = 0, $entradas = $leer($directorio, 2); $i < $entradas; $i++) {
            $entrada = $directorio + 2 + $i * 12;

            if ($leer($entrada, 2) === 0x0112) {
                $valor = $leer($entrada + 8, 2);

                return $valor >= 1 && $valor <= 8 ? $valor : 1;
            }
        }

        return 1;
    }

    private function webp(GdImage $imagen, int $calidad): string
    {
        ob_start();

        try {
            $ok = imagewebp($imagen, null, $calidad);
        } finally {
            $bytes = ob_get_clean(); // siempre se cierra, aunque GD falle
        }

        if (! $ok || $bytes === '' || $bytes === false) {
            throw new \RuntimeException('imagewebp'); // termina en el log de procesar()
        }

        return $bytes;
    }
}

<?php

/**
 * Genera los iconos PNG de la PWA con GD (sin dependencias externas).
 *
 * Uso:  php scripts/generar-iconos.php
 * Crea: public/iconos/icono-192.png, icono-512.png,
 *       icono-512-maskable.png y apple-touch-icon.png (180px).
 */
$directorio = __DIR__.'/../public/iconos';

if (! is_dir($directorio)) {
    mkdir($directorio, 0775, true);
}

/**
 * @param  float  $zonaSegura  proporción del lienzo que ocupa el texto
 */
function generarIcono(string $ruta, int $tamano, float $zonaSegura = 0.62): void
{
    $imagen = imagecreatetruecolor($tamano, $tamano);

    // Fondo azul (tema de la app) con una franja inferior más oscura.
    $azul = imagecolorallocate($imagen, 30, 64, 175);      // #1e40af
    $azulOscuro = imagecolorallocate($imagen, 23, 37, 84); // #172554
    $blanco = imagecolorallocate($imagen, 255, 255, 255);
    $amarillo = imagecolorallocate($imagen, 250, 204, 21); // #facc15 (guiño a la grúa)

    imagefilledrectangle($imagen, 0, 0, $tamano, $tamano, $azul);
    imagefilledrectangle($imagen, 0, (int) ($tamano * 0.82), $tamano, $tamano, $azulOscuro);
    imagefilledrectangle($imagen, 0, (int) ($tamano * 0.80), $tamano, (int) ($tamano * 0.82), $amarillo);

    $texto = 'FT';
    $fuentes = [
        'C:/Windows/Fonts/arialbd.ttf',
        'C:/Windows/Fonts/segoeuib.ttf',
        '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
    ];

    $fuente = null;
    foreach ($fuentes as $candidata) {
        if (is_file($candidata)) {
            $fuente = $candidata;
            break;
        }
    }

    if ($fuente !== null) {
        // Ajusta el tamaño de fuente al área segura.
        $puntos = $tamano * $zonaSegura * 0.55;
        $caja = imagettfbbox($puntos, 0, $fuente, $texto);
        $anchoTexto = $caja[2] - $caja[0];
        $altoTexto = $caja[1] - $caja[7];
        $x = (int) (($tamano - $anchoTexto) / 2 - $caja[0]);
        $y = (int) (($tamano - $altoTexto) / 2 + $altoTexto - $caja[1] - $tamano * 0.02);
        imagettftext($imagen, $puntos, 0, $x, $y, $blanco, $fuente, $texto);
    } else {
        // Respaldo sin TTF: fuente bitmap centrada.
        $x = (int) ($tamano / 2 - 20);
        $y = (int) ($tamano / 2 - 8);
        imagestring($imagen, 5, $x, $y, $texto, $blanco);
    }

    imagepng($imagen, $ruta, 9);
    imagedestroy($imagen);

    echo basename($ruta).' generado ('.$tamano.'px)'.PHP_EOL;
}

generarIcono($directorio.'/icono-192.png', 192);
generarIcono($directorio.'/icono-512.png', 512);
generarIcono($directorio.'/icono-512-maskable.png', 512, 0.45); // contenido dentro de la zona segura maskable
generarIcono($directorio.'/apple-touch-icon.png', 180);

echo 'Listo.'.PHP_EOL;

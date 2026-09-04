<?php

/**
 * Verifica que la interfaz esté completamente traducida al inglés.
 *
 * Hace dos pasadas distintas:
 *
 *   1. COBERTURA — cada clave que el código pide traducir (__(), trans_choice(),
 *      #[Title()], ->title()) tiene que existir en lang/en.json.
 *
 *   2. DETECCIÓN — texto en español que nunca se envolvió y que tampoco existe
 *      como clave. Esta pasada es la que faltaba: la versión anterior de este
 *      script solo miraba resources/views, así que todo el lado PHP (Enums,
 *      Livewire, Services, Exports) quedó sin auditar.
 *
 * Un literal que ya figura como clave en en.json nunca se marca: da igual si se
 * traduce donde se escribe o al mostrarlo. Las notas del historial, por ejemplo,
 * se guardan en español a propósito y se traducen en la vista.
 *
 * Uso: php scripts/verificar-traducciones.php
 * Devuelve 1 si algo falta, para poder usarlo como puerta antes de un commit.
 */
$base = realpath(__DIR__.'/..');
$en = json_decode(file_get_contents("$base/lang/en.json"), true);

if ($en === null) {
    echo 'JSON INVALIDO: '.json_last_error_msg().PHP_EOL;
    exit(1);
}

echo 'en.json valido con '.count($en).' claves'.PHP_EOL;

/* --------------------------------------------------------------------------
 | Qué se escanea
 |--------------------------------------------------------------------------*/

$directorios = [
    "$base/resources/views" => 'blade.php',
    "$base/app" => 'php',
];

// Carpetas cuyo texto llega al usuario. Quedan fuera, a propósito, las órdenes
// de consola (app/Console): son herramientas de administración, no interfaz.
$carpetasDeInterfaz = ['/app/Livewire', '/app/Enums', '/app/Exports', '/app/Services', '/app/Models', '/app/Http'];

// Literales legítimos que el detector confundiría con texto de interfaz.
$permitidos = ['Forte Towing'];

function archivos(string $dir, string $extension): array
{
    if (! is_dir($dir)) {
        return [];
    }

    $encontrados = [];
    $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));

    foreach ($rii as $f) {
        if ($f->isFile() && str_ends_with($f->getFilename(), '.'.$extension)) {
            $encontrados[] = $f->getPathname();
        }
    }

    sort($encontrados);

    return $encontrados;
}

/** Ruta corta y con barras normales, para que el informe se lea. */
function relativa(string $base, string $ruta): string
{
    return ltrim(str_replace(DIRECTORY_SEPARATOR, '/', str_replace($base, '', $ruta)), '/');
}

/** Quita comentarios para que no ensucien la detección. */
function sinComentarios(string $txt): string
{
    $txt = preg_replace('/\{\{--.*?--\}\}/s', '', $txt);
    $txt = preg_replace('#/\*.*?\*/#s', '', $txt);
    $txt = preg_replace('#^\s*(//|\#).*$#m', '', $txt);
    $txt = preg_replace('/<!--.*?-->/s', '', $txt);           // HTML

    return $txt;
}

/**
 * Claves que el código pide traducir. Además de __(), reconoce los títulos de
 * página: van en un atributo PHP, que no admite llamadas a función, así que el
 * texto en español es la clave y la traducción ocurre en el layout.
 */
function clavesPedidas(string $txt): array
{
    $patrones = [
        "/__\(\s*'((?:[^'\\\\]|\\\\.)*)'/",
        "/__\(\s*\"((?:[^\"\\\\]|\\\\.)*)\"/",
        "/trans_choice\(\s*'((?:[^'\\\\]|\\\\.)*)'/",
        "/#\[Title\(\s*'((?:[^'\\\\]|\\\\.)*)'/",
        "/->title\(\s*'((?:[^'\\\\]|\\\\.)*)'/",
    ];

    $claves = [];

    foreach ($patrones as $patron) {
        if (preg_match_all($patron, $txt, $m)) {
            foreach ($m[1] as $clave) {
                $claves[] = str_replace(["\\'", '\\"'], ["'", '"'], $clave);
            }
        }
    }

    return $claves;
}

/**
 * Claves cortas que se resuelven en lang/xx/*.php y no en el JSON, como
 * "auth.password" o "validation.attributes.precio_compra". Se exige que todos
 * los segmentos estén en minúscula para no tragarse por error un texto real
 * terminado en punto, como "Guardado.".
 */
$esClaveDeArchivo = fn (string $k) => (bool) preg_match('/^[a-z0-9_]+(\.[a-z0-9_*]+)+$/', $k);

/* --------------------------------------------------------------------------
 | Pasada 1: cobertura
 |--------------------------------------------------------------------------*/

$faltan = [];
$revisados = 0;
$pedidas = 0;

foreach ($directorios as $dir => $extension) {
    foreach (archivos($dir, $extension) as $ruta) {
        $revisados++;
        $txt = sinComentarios(file_get_contents($ruta));

        foreach (clavesPedidas($txt) as $clave) {
            $pedidas++;

            if ($esClaveDeArchivo($clave) || array_key_exists($clave, $en)) {
                continue;
            }

            $faltan[$clave][] = relativa($base, $ruta);
        }
    }
}

/* --------------------------------------------------------------------------
 | Pasada 2: detección de texto sin traducir
 |--------------------------------------------------------------------------*/

/** ¿Este literal parece texto para el usuario, y no un identificador interno? */
function pareceTexto(string $s): bool
{
    $s = trim($s);

    if ($s === '' || mb_strlen($s) < 3) {
        return false;
    }

    // Caracteres que solo existen en español: basta uno.
    if (preg_match('/[áéíóúüñÁÉÍÓÚÜÑ¿¡]/u', $s)) {
        return true;
    }

    // Frase que empieza en mayúscula y tiene dos o más palabras de verdad.
    // Deja fuera los formatos de fecha ("Y-m-d H:i") y las clases CSS, que van
    // siempre en minúscula.
    if (preg_match('/^[A-ZÁÉÍÓÚÑ]/u', $s) && str_contains($s, ' ')) {
        preg_match_all('/\p{L}{3,}/u', $s, $palabras);

        return count($palabras[0]) >= 2;
    }

    return false;
}

$sinTraducir = [];

foreach (archivos("$base/app", 'php') as $ruta) {
    $relativa = str_replace('\\', '/', str_replace($base, '', $ruta));

    $deInterfaz = false;

    foreach ($carpetasDeInterfaz as $carpeta) {
        $deInterfaz = $deInterfaz || str_starts_with($relativa, $carpeta);
    }

    if (! $deInterfaz) {
        continue;
    }

    $txt = sinComentarios(file_get_contents($ruta));

    // Se borra el primer argumento de las llamadas ya traducidas: lo que quede
    // es texto suelto.
    $resto = preg_replace("/__\(\s*'(?:[^'\\\\]|\\\\.)*'/", '__(', $txt);
    $resto = preg_replace("/trans_choice\(\s*'(?:[^'\\\\]|\\\\.)*'/", 'trans_choice(', $resto);

    if (! preg_match_all("/'((?:[^'\\\\]|\\\\.)*)'/", $resto, $m)) {
        continue;
    }

    foreach ($m[1] as $literal) {
        $literal = str_replace("\\'", "'", $literal);

        if (in_array($literal, $permitidos, true) || array_key_exists($literal, $en) || ! pareceTexto($literal)) {
            continue;
        }

        $sinTraducir[$literal][] = ltrim($relativa, '/');
    }
}

// En las vistas basta con buscar acentos fuera de {{ }}: una palabra acentuada
// suelta en un Blade es texto que se olvidaron de envolver.
foreach (archivos("$base/resources/views", 'blade.php') as $ruta) {
    $txt = sinComentarios(file_get_contents($ruta));
    $txt = preg_replace('/\{\{.*?\}\}|\{!!.*?!!\}|@php.*?@endphp/s', '', $txt);
    // Directivas con sus argumentos, incluidos los parentesis anidados.
    $txt = preg_replace('/@\w+\s*(\((?:[^()]++|(?1))*\))?/s', '', $txt);
    $txt = preg_replace('/<[^>]*>/s', ' ', $txt);

    foreach (preg_split('/\r?\n/', $txt) as $linea) {
        $linea = trim($linea);

        if ($linea !== '' && preg_match('/[áéíóúñÁÉÍÓÚÑ¿¡]/u', $linea) && ! array_key_exists($linea, $en)) {
            $sinTraducir[$linea][] = ltrim(str_replace('\\', '/', str_replace($base, '', $ruta)), '/');
        }
    }
}

/* --------------------------------------------------------------------------
 | Informe
 |--------------------------------------------------------------------------*/

echo "revisados $revisados archivos, $pedidas usos de traduccion".PHP_EOL.PHP_EOL;

$problemas = 0;

if ($faltan !== []) {
    $problemas++;
    echo 'FALTAN '.count($faltan).' claves en en.json:'.PHP_EOL;

    foreach ($faltan as $clave => $rutas) {
        echo '  - '.$clave.'   ['.implode(', ', array_unique($rutas)).']'.PHP_EOL;
    }

    echo PHP_EOL;
} else {
    echo 'COBERTURA OK: todas las claves usadas estan en en.json'.PHP_EOL;
}

if ($sinTraducir !== []) {
    $problemas++;
    echo 'TEXTO SIN TRADUCIR ('.count($sinTraducir).'):'.PHP_EOL;

    foreach ($sinTraducir as $literal => $rutas) {
        echo '  - '.$literal.'   ['.implode(', ', array_unique($rutas)).']'.PHP_EOL;
    }

    echo PHP_EOL;
} else {
    echo 'DETECCION OK: no hay texto en espanol suelto'.PHP_EOL;
}

exit($problemas > 0 ? 1 : 0);

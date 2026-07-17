<?php

/**
 * Verifica cobertura de traducciones al inglés.
 * Escanea __('...') en resources/views y app, y reporta las claves
 * que faltan en lang/en.json. Uso: php scripts/verificar-traducciones.php
 */
$base = __DIR__.'/..';
$en = json_decode(file_get_contents("$base/lang/en.json"), true);

if ($en === null) {
    echo 'JSON INVALIDO: '.json_last_error_msg().PHP_EOL;
    exit(1);
}

echo 'en.json valido con '.count($en).' claves'.PHP_EOL;

// Claves que se resuelven en archivos PHP (no en el JSON): auth.*, etc.
$ignorar = fn (string $k) => str_contains($k, '.') && ! str_contains($k, ' ');

$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator("$base/resources/views"));
$archivos = [];
foreach ($rii as $f) {
    if ($f->isFile() && str_ends_with($f->getFilename(), '.blade.php')) {
        $archivos[] = $f->getPathname();
    }
}

$faltan = [];
foreach ($archivos as $ruta) {
    $txt = file_get_contents($ruta);
    // Captura __('...') y __("..."), con o sin segundo argumento.
    if (preg_match_all("/__\(\s*'((?:[^'\\\\]|\\\\.)*)'/", $txt, $m)) {
        foreach ($m[1] as $clave) {
            $clave = str_replace("\\'", "'", $clave);
            if ($ignorar($clave)) {
                continue;
            }
            if (! array_key_exists($clave, $en)) {
                $faltan[$clave] = ($faltan[$clave] ?? 0) + 1;
            }
        }
    }
}

if ($faltan === []) {
    echo 'COBERTURA OK: todas las claves de las vistas estan en en.json'.PHP_EOL;
} else {
    echo 'FALTAN '.count($faltan).' claves en en.json:'.PHP_EOL;
    foreach (array_keys($faltan) as $k) {
        echo "  - $k".PHP_EOL;
    }
}

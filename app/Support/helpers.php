<?php

if (! function_exists('dinero')) {
    /**
     * Formatea un monto como dinero en dólares: 1234.5 => "$1,234.50".
     */
    function dinero(float|int|string|null $valor): string
    {
        return '$'.number_format((float) ($valor ?? 0), 2, '.', ',');
    }
}

if (! function_exists('nombreCampo')) {
    /**
     * Nombre legible de un campo, en el idioma del usuario: "precio_compra"
     * pasa a "precio de compra" o "purchase price".
     *
     * Reutiliza el bloque 'attributes' de los validation.php de cada idioma,
     * que ya existe para los mensajes de validación: un solo diccionario para
     * las dos cosas.
     * Si el campo no está listado, devuelve el nombre con guiones bajos
     * convertidos en espacios, que siempre es más legible que la clave cruda.
     */
    function nombreCampo(string $campo): string
    {
        $clave = "validation.attributes.$campo";
        $traducido = __($clave);

        return $traducido === $clave ? str_replace('_', ' ', $campo) : $traducido;
    }
}

if (! function_exists('valorCampo')) {
    /**
     * Valor legible de un dato de auditoría, en el idioma del usuario.
     *
     * Los detalles de auditoría guardan valores neutros ("casa_hugo",
     * "efectivo", true) a propósito: si guardáramos la etiqueta ya traducida,
     * cada fila quedaría en el idioma de quien hizo la acción. La traducción
     * se hace aquí, al mostrar.
     *
     * Las filas viejas guardaron la etiqueta en texto: no coinciden con ningún
     * caso del enum y se devuelven tal cual, que es lo correcto para un
     * registro histórico.
     */
    function valorCampo(string $campo, mixed $valor): string
    {
        if (is_bool($valor)) {
            return $valor ? __('Sí') : __('No');
        }

        // Las notas del sistema se guardan en español y son su propia clave;
        // las que escribió una persona no coinciden con ninguna y salen tal cual.
        if ($campo === 'nota' && is_string($valor)) {
            return __($valor);
        }

        $enums = [
            'categoria' => App\Enums\CategoriaGasto::class,
            'de' => App\Enums\EstadoVehiculo::class,
            'a' => App\Enums\EstadoVehiculo::class,
            'estado' => App\Enums\EstadoVehiculo::class,
            'pago' => App\Enums\MetodoPagoGruero::class,
            'metodo_pago_gruero' => App\Enums\MetodoPagoGruero::class,
            'metodo_pago' => App\Enums\MetodoPago::class,
            'destino' => App\Enums\UbicacionDestino::class,
            'ubicacion_destino' => App\Enums\UbicacionDestino::class,
            'titulacion' => App\Enums\EstadoTitulo::class,
            'estado_titulo' => App\Enums\EstadoTitulo::class,
            'etapa' => App\Enums\EtapaFoto::class,
        ];

        if (isset($enums[$campo]) && is_string($valor)) {
            return $enums[$campo]::tryFrom($valor)?->etiqueta() ?? $valor;
        }

        return (string) $valor;
    }
}

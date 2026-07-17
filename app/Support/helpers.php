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

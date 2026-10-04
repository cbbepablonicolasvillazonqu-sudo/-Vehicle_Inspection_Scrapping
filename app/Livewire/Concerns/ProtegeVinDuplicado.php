<?php

namespace App\Livewire\Concerns;

use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;

/**
 * Red de seguridad para el VIN, compartida por todo componente que lo guarda.
 *
 * La regla de validación (Vehicle::reglasVin) ya cubre el caso normal, pero
 * queda una ventana entre validar y escribir: si dos personas guardan el mismo
 * VIN a la vez, la segunda pasa la validación y choca contra el índice único.
 * El usuario tiene que ver el mismo mensaje en los dos casos, nunca un 500.
 */
trait ProtegeVinDuplicado
{
    /** Convierte el choque contra el índice único del VIN en un error de validación. */
    protected function sinChocarConElVin(callable $guardar): mixed
    {
        try {
            return $guardar();
        } catch (QueryException $e) {
            if (! $this->esVinDuplicado($e)) {
                throw $e;
            }

            throw ValidationException::withMessages([
                'vin' => __('validation.custom.vin.unique'),
            ]);
        }
    }

    /** ¿La excepción es la violación del índice único del VIN, y no otra cosa? */
    protected function esVinDuplicado(QueryException $e): bool
    {
        return (string) $e->getCode() === '23000'
            && str_contains($e->getMessage(), 'vehicles_vin_unique');
    }
}

<?php

namespace App\Exports\Concerns;

use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Shared\StringHelper;

/**
 * Que un texto de la base nunca se ejecute como fórmula al abrir el archivo.
 *
 * PhpSpreadsheet escribe como fórmula una nota que empiece con "=", y Excel,
 * al abrir un CSV, interpreta igual lo que empieza con "+", "-" o "@". Esos
 * textos van como texto y, en CSV (que no tiene tipos), con un apóstrofo
 * delante. Todo lo demás (números, fechas, vacíos) se escribe como antes.
 */
trait SinFormulas
{
    public function bindValue(Cell $cell, mixed $value): bool
    {
        if (is_string($value) && ! is_numeric($value) && preg_match('/^[=+\-@\t\r]/', $value)) {
            $cell->setValueExplicit(
                $this->prefijoContraFormulas().StringHelper::sanitizeUTF8($value),
                DataType::TYPE_STRING,
            );

            return true;
        }

        return (new DefaultValueBinder)->bindValue($cell, $value);
    }

    /** Solo el CSV lleva el apóstrofo: en XLSX la celda ya queda como texto. */
    protected function prefijoContraFormulas(): string
    {
        return '';
    }
}

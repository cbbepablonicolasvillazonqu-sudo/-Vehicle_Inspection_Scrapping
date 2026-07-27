<?php

namespace App\Enums;

/**
 * Etapas de las fotos:
 * - Vehiculo: la foto principal, que carga el Admin desde el formulario.
 * - Gasto: las que se adjuntan a cada gasto.
 */
enum EtapaFoto: string
{
    case Vehiculo = 'vehiculo';
    case Gasto = 'gasto';

    public function etiqueta(): string
    {
        return __(match ($this) {
            self::Vehiculo => 'Vehículo',
            self::Gasto => 'Gasto',
        });
    }

    /** @return array<string, string> */
    public static function opciones(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $caso) => [$caso->value => $caso->etiqueta()])
            ->all();
    }
}

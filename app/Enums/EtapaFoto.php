<?php

namespace App\Enums;

/**
 * Etapas de las fotos. Desde el rediseño, las fotos solo se cargan dentro
 * del módulo de Gastos: las etapas compra / reparación / venta se retiraron.
 */
enum EtapaFoto: string
{
    case Gasto = 'gasto';

    public function etiqueta(): string
    {
        return __(match ($this) {
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

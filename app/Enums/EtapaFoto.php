<?php

namespace App\Enums;

/**
 * Etapas en las que se organizan las fotos de cada vehículo
 * (carpetas separadas en el disco).
 */
enum EtapaFoto: string
{
    case Compra = 'compra';
    case Reparacion = 'reparacion';
    case Venta = 'venta';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Compra => 'Compra',
            self::Reparacion => 'Reparación',
            self::Venta => 'Venta',
        };
    }

    /** @return array<string, string> */
    public static function opciones(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $caso) => [$caso->value => $caso->etiqueta()])
            ->all();
    }
}

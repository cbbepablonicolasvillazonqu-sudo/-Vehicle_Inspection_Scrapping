<?php

namespace App\Enums;

/**
 * Forma de pago que usó el Gruero para pagar el vehículo al recogerlo.
 */
enum MetodoPagoGruero: string
{
    case Efectivo = 'efectivo';
    case Zelle = 'zelle';

    public function etiqueta(): string
    {
        return __(match ($this) {
            self::Efectivo => 'Efectivo',
            self::Zelle => 'Zelle',
        });
    }

    public function colorBadge(): string
    {
        return match ($this) {
            self::Efectivo => 'bg-green-100 text-green-800',
            self::Zelle => 'bg-purple-100 text-purple-800',
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

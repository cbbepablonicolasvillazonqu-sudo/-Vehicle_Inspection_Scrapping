<?php

namespace App\Enums;

enum MetodoPago: string
{
    case Efectivo = 'efectivo';
    case Zelle = 'zelle';
    case Cheque = 'cheque';
    case Tarjeta = 'tarjeta';
    case Financiado = 'financiado';
    case Otro = 'otro';

    public function etiqueta(): string
    {
        return __(match ($this) {
            self::Efectivo => 'Efectivo',
            self::Zelle => 'Zelle',
            self::Cheque => 'Cheque',
            self::Tarjeta => 'Tarjeta',
            self::Financiado => 'Financiado',
            self::Otro => 'Otro',
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

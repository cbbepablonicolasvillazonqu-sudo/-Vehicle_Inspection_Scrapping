<?php

namespace App\Enums;

enum LugarCompra: string
{
    case Subasta = 'subasta';
    case Particular = 'particular';
    case Otro = 'otro';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Subasta => 'Subasta',
            self::Particular => 'Particular',
            self::Otro => 'Otro',
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

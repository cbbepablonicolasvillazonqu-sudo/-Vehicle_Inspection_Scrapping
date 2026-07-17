<?php

namespace App\Enums;

enum EstadoTitulo: string
{
    case EnMano = 'en_mano';
    case Pendiente = 'pendiente';
    case Salvage = 'salvage';

    public function etiqueta(): string
    {
        return match ($this) {
            self::EnMano => 'En mano',
            self::Pendiente => 'Pendiente',
            self::Salvage => 'Salvage',
        };
    }

    public function colorBadge(): string
    {
        return match ($this) {
            self::EnMano => 'bg-green-100 text-green-800',
            self::Pendiente => 'bg-yellow-100 text-yellow-800',
            self::Salvage => 'bg-red-100 text-red-800',
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

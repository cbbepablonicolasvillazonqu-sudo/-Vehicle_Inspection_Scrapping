<?php

namespace App\Enums;

/**
 * Dónde está el vehículo. Lista fija acordada con el negocio.
 */
enum UbicacionDestino: string
{
    case Oficina1Aldi = 'oficina_1_aldi';
    case CasaHugo = 'casa_hugo';

    public function etiqueta(): string
    {
        return __(match ($this) {
            self::Oficina1Aldi => 'OFICINA 1 ALDI',
            self::CasaHugo => 'CASA HUGO',
        });
    }

    public function colorBadge(): string
    {
        return match ($this) {
            self::Oficina1Aldi => 'bg-indigo-100 text-indigo-800',
            self::CasaHugo => 'bg-amber-100 text-amber-800',
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

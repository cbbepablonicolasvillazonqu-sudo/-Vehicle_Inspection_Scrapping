<?php

namespace App\Enums;

/**
 * Dónde quedó estacionado el vehículo tras el recojo.
 * Lista fija acordada con el negocio.
 */
enum UbicacionDestino: string
{
    case Oficina1 = 'oficina_1';
    case Aldi = 'aldi';
    case CasaHugo = 'casa_hugo';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Oficina1 => 'OFICINA 1',
            self::Aldi => 'ALDI',
            self::CasaHugo => 'CASA HUGO',
        };
    }

    public function colorBadge(): string
    {
        return match ($this) {
            self::Oficina1 => 'bg-indigo-100 text-indigo-800',
            self::Aldi => 'bg-teal-100 text-teal-800',
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

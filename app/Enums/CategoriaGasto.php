<?php

namespace App\Enums;

enum CategoriaGasto: string
{
    case Reparacion = 'reparacion';
    case Piezas = 'piezas';
    case GruaTransporte = 'grua_transporte';
    case TituloTramites = 'titulo_tramites';
    case Otro = 'otro';

    public function etiqueta(): string
    {
        return __(match ($this) {
            self::Reparacion => 'Reparación',
            self::Piezas => 'Piezas',
            self::GruaTransporte => 'Grúa / transporte',
            self::TituloTramites => 'Título / trámites',
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

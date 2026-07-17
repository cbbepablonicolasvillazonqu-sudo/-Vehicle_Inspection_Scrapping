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

    /** Clases Tailwind para el chip de la categoría (diferenciar de un vistazo). */
    public function colorChip(): string
    {
        return match ($this) {
            self::Reparacion => 'bg-amber-100 text-amber-800',
            self::Piezas => 'bg-indigo-100 text-indigo-800',
            self::GruaTransporte => 'bg-cyan-100 text-cyan-800',
            self::TituloTramites => 'bg-violet-100 text-violet-800',
            self::Otro => 'bg-slate-100 text-slate-700',
        };
    }

    /** Icono representativo (nombres del componente x-icono). */
    public function icono(): string
    {
        return match ($this) {
            self::Reparacion => 'llave-inglesa',
            self::Piezas => 'engranaje',
            self::GruaTransporte => 'vehiculo',
            self::TituloTramites => 'archivo',
            self::Otro => 'etiqueta',
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

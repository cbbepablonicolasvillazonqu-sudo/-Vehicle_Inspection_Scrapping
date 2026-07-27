<?php

namespace App\Enums;

/**
 * Estados del ciclo de vida de un vehículo.
 *
 * Colores acordados con el cliente:
 * amarillo = reparación, verde = listo, azul = vendido, gris = desguace.
 */
enum EstadoVehiculo: string
{
    case Comprado = 'comprado';
    case EnReparacion = 'en_reparacion';
    case Listo = 'listo';
    case Publicado = 'publicado';
    case Vendido = 'vendido';
    case Desguace = 'desguace';

    public function etiqueta(): string
    {
        return __(match ($this) {
            self::Comprado => 'Comprado / pendiente de revisión',
            self::EnReparacion => 'En reparación',
            self::Listo => 'Listo para la venta',
            self::Publicado => 'Publicado / en venta',
            self::Vendido => 'Vendido',
            self::Desguace => 'Junk car',
        });
    }

    public function etiquetaCorta(): string
    {
        return __(match ($this) {
            self::Comprado => 'Comprado',
            self::EnReparacion => 'En reparación',
            self::Listo => 'Listo',
            self::Publicado => 'Publicado',
            self::Vendido => 'Vendido',
            self::Desguace => 'Junk car',
        });
    }

    /** Clases Tailwind para la insignia del estado. */
    public function colorBadge(): string
    {
        return match ($this) {
            self::Comprado => 'bg-orange-100 text-orange-800 ring-1 ring-orange-300',
            self::EnReparacion => 'bg-yellow-100 text-yellow-800 ring-1 ring-yellow-300',
            self::Listo => 'bg-green-100 text-green-800 ring-1 ring-green-300',
            self::Publicado => 'bg-sky-100 text-sky-800 ring-1 ring-sky-300',
            self::Vendido => 'bg-blue-100 text-blue-800 ring-1 ring-blue-300',
            self::Desguace => 'bg-gray-200 text-gray-700 ring-1 ring-gray-400',
        };
    }

    /** Clases Tailwind para botones de acción hacia este estado. */
    public function colorBoton(): string
    {
        return match ($this) {
            self::Comprado => 'bg-orange-600 hover:bg-orange-700',
            self::EnReparacion => 'bg-yellow-500 hover:bg-yellow-600',
            self::Listo => 'bg-green-600 hover:bg-green-700',
            self::Publicado => 'bg-sky-600 hover:bg-sky-700',
            self::Vendido => 'bg-blue-700 hover:bg-blue-800',
            self::Desguace => 'bg-gray-600 hover:bg-gray-700',
        };
    }

    /** Punto de color para tarjetas y listas. */
    public function colorPunto(): string
    {
        return match ($this) {
            self::Comprado => 'bg-orange-500',
            self::EnReparacion => 'bg-yellow-400',
            self::Listo => 'bg-green-500',
            self::Publicado => 'bg-sky-500',
            self::Vendido => 'bg-blue-600',
            self::Desguace => 'bg-gray-500',
        };
    }

    /** Estados finales: el registro queda bloqueado para todos menos Admin. */
    public function esFinal(): bool
    {
        return in_array($this, [self::Vendido, self::Desguace], true);
    }

    /** @return array<string, string> valor => etiqueta (para selects). */
    public static function opciones(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $estado) => [$estado->value => $estado->etiqueta()])
            ->all();
    }
}

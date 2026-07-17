<?php

namespace App\Livewire;

use App\Enums\EstadoVehiculo;
use App\Models\Vehicle;
use App\Services\ServicioRentabilidad;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Panel principal: tarjetas con el conteo por estado (según lo que el rol
 * puede ver) y, para Admin, total invertido y ganancia del mes/acumulada.
 * Debajo se incrusta la lista de vehículos con búsqueda y filtro.
 */
#[Layout('layouts.app')]
#[Title('Panel')]
class Dashboard extends Component
{
    public function render()
    {
        $usuario = auth()->user();

        $base = fn () => Vehicle::query()->visiblePara($usuario);

        $conteos = [
            'inventario' => $base()->whereNotIn('estado', [
                EstadoVehiculo::Vendido->value,
                EstadoVehiculo::Desguace->value,
            ])->count(),
            'reparacion' => $base()->where('estado', EstadoVehiculo::EnReparacion)->count(),
            'listos' => $base()->where('estado', EstadoVehiculo::Listo)->count(),
            'publicados' => $base()->where('estado', EstadoVehiculo::Publicado)->count(),
            'vendidosMes' => $base()->where('estado', EstadoVehiculo::Vendido)
                ->whereHas('venta', fn ($q) => $q->whereBetween('fecha_venta', [
                    now()->startOfMonth()->toDateString(),
                    now()->endOfMonth()->toDateString(),
                ]))->count(),
            'desguace' => $base()->where('estado', EstadoVehiculo::Desguace)->count(),
        ];

        $finanzas = null;

        if ($usuario->can('ver ganancias')) {
            $rentabilidad = app(ServicioRentabilidad::class);

            $finanzas = [
                'invertido' => $rentabilidad->totalInvertidoInventario(),
                'gananciaMes' => $rentabilidad->gananciaDelMes(now()),
                'gananciaAcumulada' => $rentabilidad->gananciaAcumulada(),
            ];
        }

        return view('livewire.dashboard', [
            'conteos' => $conteos,
            'finanzas' => $finanzas,
        ]);
    }
}

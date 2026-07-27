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

        // Tarjetas que ve cada rol:
        // - Mecánico: sin "Publicados" ni "Vendidos este mes".
        // - Vendedor: sin "En reparación" ni "Junk car".
        // - Gruero: solo su inventario asignado.
        $ocultas = match (true) {
            $usuario->hasRole('mecanico') => ['publicados', 'vendidosMes'],
            $usuario->hasRole('vendedor') => ['reparacion', 'desguace'],
            $usuario->hasRole('gruero') => ['reparacion', 'listos', 'publicados', 'vendidosMes'],
            default => [],
        };

        $tarjetas = collect([
            ['clave' => 'inventario', 'txt' => __('En inventario'), 'icono' => 'archivo', 'color' => 'slate', 'estado' => null],
            ['clave' => 'reparacion', 'txt' => __('En reparación'), 'icono' => 'llave-inglesa', 'color' => 'yellow', 'estado' => 'en_reparacion'],
            ['clave' => 'listos', 'txt' => __('Listos'), 'icono' => 'check', 'color' => 'green', 'estado' => 'listo'],
            ['clave' => 'publicados', 'txt' => __('Publicados'), 'icono' => 'etiqueta', 'color' => 'sky', 'estado' => 'publicado'],
            ['clave' => 'vendidosMes', 'txt' => __('Vendidos este mes'), 'icono' => 'dinero', 'color' => 'blue', 'estado' => 'vendido'],
            ['clave' => 'desguace', 'txt' => __('Junk car'), 'icono' => 'engranaje', 'color' => 'gray', 'estado' => 'desguace'],
        ])
            ->reject(fn (array $t) => in_array($t['clave'], $ocultas, true))
            ->map(fn (array $t) => $t + ['n' => $conteos[$t['clave']]])
            ->values()
            ->all();

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
            'tarjetas' => $tarjetas,
            'finanzas' => $finanzas,
        ]);
    }
}

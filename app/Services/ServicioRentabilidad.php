<?php

namespace App\Services;

use App\Enums\EstadoVehiculo;
use App\Models\Expense;
use App\Models\Sale;
use App\Models\ScrapRecord;
use App\Models\Vehicle;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Cálculos de rentabilidad (visibles solo para Admin):
 * ganancia = (precio de venta o monto de desguace) − precio de compra − gastos.
 */
class ServicioRentabilidad
{
    /**
     * Salidas del inventario (ventas + desguaces) con su ganancia,
     * opcionalmente acotadas a un rango de fechas.
     *
     * @return Collection<int, array{tipo: string, vehiculo: Vehicle, fecha: CarbonInterface, compra: float, gastos: float, recuperado: float, ganancia: float}>
     */
    public function salidas(?CarbonInterface $desde = null, ?CarbonInterface $hasta = null): Collection
    {
        $conGastos = fn ($q) => $q->withSum('gastos as gastos_total', 'monto')->withTrashed();

        $ventas = Sale::query()
            ->with(['vehiculo' => $conGastos, 'usuario'])
            ->when($desde, fn ($q) => $q->whereDate('fecha_venta', '>=', $desde))
            ->when($hasta, fn ($q) => $q->whereDate('fecha_venta', '<=', $hasta))
            ->get()
            ->filter(fn (Sale $venta) => $venta->vehiculo !== null)
            ->map(function (Sale $venta) {
                $vehiculo = $venta->vehiculo;
                $gastos = (float) ($vehiculo->gastos_total ?? 0);
                $compra = (float) $vehiculo->precio_compra;
                $recuperado = (float) $venta->precio_venta;

                return [
                    'tipo' => 'Venta',
                    'vehiculo' => $vehiculo,
                    'fecha' => $venta->fecha_venta,
                    'compra' => $compra,
                    'gastos' => $gastos,
                    'recuperado' => $recuperado,
                    'ganancia' => round($recuperado - $compra - $gastos, 2),
                ];
            });

        $desguaces = ScrapRecord::query()
            ->with(['vehiculo' => $conGastos, 'usuario'])
            ->when($desde, fn ($q) => $q->whereDate('fecha', '>=', $desde))
            ->when($hasta, fn ($q) => $q->whereDate('fecha', '<=', $hasta))
            ->get()
            ->filter(fn (ScrapRecord $registro) => $registro->vehiculo !== null)
            ->map(function (ScrapRecord $registro) {
                $vehiculo = $registro->vehiculo;
                $gastos = (float) ($vehiculo->gastos_total ?? 0);
                $compra = (float) $vehiculo->precio_compra;
                $recuperado = (float) $registro->monto_recibido;

                return [
                    'tipo' => 'Desguace',
                    'vehiculo' => $vehiculo,
                    'fecha' => $registro->fecha,
                    'compra' => $compra,
                    'gastos' => $gastos,
                    'recuperado' => $recuperado,
                    'ganancia' => round($recuperado - $compra - $gastos, 2),
                ];
            });

        return $ventas->concat($desguaces)->sortByDesc('fecha')->values();
    }

    public function gananciaEntre(?CarbonInterface $desde = null, ?CarbonInterface $hasta = null): float
    {
        return round($this->salidas($desde, $hasta)->sum('ganancia'), 2);
    }

    public function gananciaDelMes(CarbonInterface $mes): float
    {
        return $this->gananciaEntre($mes->copy()->startOfMonth(), $mes->copy()->endOfMonth());
    }

    public function gananciaAcumulada(): float
    {
        return $this->gananciaEntre();
    }

    /** Capital inmovilizado: compra + gastos de los vehículos aún en inventario. */
    public function totalInvertidoInventario(): float
    {
        $activos = Vehicle::query()->whereNotIn('estado', [
            EstadoVehiculo::Vendido->value,
            EstadoVehiculo::Desguace->value,
        ]);

        $compra = (float) (clone $activos)->sum('precio_compra');
        $gastos = (float) Expense::whereIn('vehicle_id', (clone $activos)->select('id'))->sum('monto');

        return round($compra + $gastos, 2);
    }
}

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
 * ganancia = (precio de venta o monto de Junk car) − precio de compra − gastos.
 *
 * Una salida queda "pendiente de valorar" cuando falta un dato imprescindible:
 * el monto del Junk car (envío masivo aún sin completar) o el precio de compra
 * (vehículo asignado que nunca se recogió). Esas salidas NO suman en ninguna
 * ganancia: contarlas como 0 recuperado las convertiría en pérdidas ficticias.
 */
class ServicioRentabilidad
{
    /**
     * Salidas del inventario (ventas + Junk car) con su ganancia,
     * opcionalmente acotadas a un rango de fechas.
     *
     * @return Collection<int, array{tipo: string, vehiculo: Vehicle, fecha: CarbonInterface, compra: ?float, gastos: float, recuperado: ?float, ganancia: ?float, pendiente: bool, motivo: ?string}>
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

                return $this->fila(
                    tipo: 'Venta',
                    vehiculo: $vehiculo,
                    fecha: $venta->fecha_venta,
                    compra: $vehiculo->precio_compra,
                    recuperado: $venta->precio_venta,
                );
            });

        $desguaces = ScrapRecord::query()
            ->with(['vehiculo' => $conGastos, 'usuario'])
            ->when($desde, fn ($q) => $q->whereDate('fecha', '>=', $desde))
            ->when($hasta, fn ($q) => $q->whereDate('fecha', '<=', $hasta))
            ->get()
            ->filter(fn (ScrapRecord $registro) => $registro->vehiculo !== null)
            ->map(function (ScrapRecord $registro) {
                $vehiculo = $registro->vehiculo;

                return $this->fila(
                    tipo: 'Junk car',
                    vehiculo: $vehiculo,
                    fecha: $registro->fecha,
                    compra: $vehiculo->precio_compra,
                    recuperado: $registro->monto_recibido,
                );
            });

        return $ventas->concat($desguaces)->sortByDesc('fecha')->values();
    }

    /**
     * Arma una fila de salida marcando si se puede valorar o no.
     * Los importes nulos se conservan como null (nunca se degradan a 0).
     */
    private function fila(string $tipo, Vehicle $vehiculo, CarbonInterface $fecha, mixed $compra, mixed $recuperado): array
    {
        $gastos = (float) ($vehiculo->gastos_total ?? 0);
        $compra = $compra === null ? null : (float) $compra;
        $recuperado = $recuperado === null ? null : (float) $recuperado;

        $motivo = match (true) {
            $recuperado === null => 'sin_monto_junk',
            $compra === null => 'sin_precio_compra',
            default => null,
        };

        return [
            'tipo' => $tipo,
            'vehiculo' => $vehiculo,
            'fecha' => $fecha,
            'compra' => $compra,
            'gastos' => $gastos,
            'recuperado' => $recuperado,
            'ganancia' => $motivo === null ? round($recuperado - $compra - $gastos, 2) : null,
            'pendiente' => $motivo !== null,
            'motivo' => $motivo,
        ];
    }

    /** Salidas con todos los datos cargados: las únicas que suman ganancia. */
    public function salidasValoradas(?CarbonInterface $desde = null, ?CarbonInterface $hasta = null): Collection
    {
        return $this->salidas($desde, $hasta)->reject(fn (array $s) => $s['pendiente'])->values();
    }

    /** Salidas a las que les falta el monto del Junk car o el precio de compra. */
    public function salidasPendientes(?CarbonInterface $desde = null, ?CarbonInterface $hasta = null): Collection
    {
        return $this->salidas($desde, $hasta)->filter(fn (array $s) => $s['pendiente'])->values();
    }

    /** Cuántas salidas quedan sin valorar en toda la historia. */
    public function contarPendientesDeValorar(): int
    {
        return $this->salidasPendientes()->count();
    }

    public function gananciaEntre(?CarbonInterface $desde = null, ?CarbonInterface $hasta = null): float
    {
        return round($this->salidasValoradas($desde, $hasta)->sum('ganancia'), 2);
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

<?php

namespace App\Exports;

use App\Services\ServicioRentabilidad;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

/**
 * Reporte de ganancias del mes (solo Admin): una fila por venta/Junk car con
 * la ganancia calculada, más una fila de totales. Las salidas que aún no se
 * pueden valorar van en un bloque aparte para que no falseen el total.
 */
class GananciasExport implements FromCollection, ShouldAutoSize, WithHeadings
{
    public function __construct(private string $mes) {}

    public function headings(): array
    {
        return [
            __('Vehículo'), __('VIN'), __('Tipo'), __('Fecha'),
            __('Precio de compra'), __('Gastos'), __('Recuperado'), __('Ganancia'),
        ];
    }

    public function collection(): Collection
    {
        $inicio = Carbon::createFromFormat('Y-m', $this->mes)->startOfMonth();
        $fin = $inicio->copy()->endOfMonth();

        $rentabilidad = app(ServicioRentabilidad::class);
        $salidas = $rentabilidad->salidasValoradas($inicio, $fin);
        $pendientes = $rentabilidad->salidasPendientes($inicio, $fin);

        $fila = fn (array $salida) => [
            $salida['vehiculo']->nombreCompleto(),
            $salida['vehiculo']->vin,
            __($salida['tipo']),
            fecha($salida['fecha']),
            $salida['compra'],
            $salida['gastos'],
            $salida['recuperado'],
            $salida['ganancia'],
        ];

        $filas = $salidas->map($fila);

        if ($filas->isNotEmpty()) {
            $filas->push([
                __('TOTAL :mes', ['mes' => $inicio->translatedFormat('F Y')]), '', '', '',
                round($salidas->sum('compra'), 2),
                round($salidas->sum('gastos'), 2),
                round($salidas->sum('recuperado'), 2),
                round($salidas->sum('ganancia'), 2),
            ]);
        }

        // Bloque aparte: no suman en el total porque les falta un dato.
        if ($pendientes->isNotEmpty()) {
            $filas->push(['', '', '', '', '', '', '', '']);
            $filas->push([__('PENDIENTES DE VALORAR (no suman)'), '', '', '', '', '', '', '']);

            foreach ($pendientes as $salida) {
                $filas->push([
                    $salida['vehiculo']->nombreCompleto(),
                    $salida['vehiculo']->vin,
                    $salida['motivo'] === 'sin_monto_junk'
                        ? __('Falta el monto del Junk car')
                        : __('Falta el precio de compra'),
                    fecha($salida['fecha']),
                    $salida['compra'],
                    $salida['gastos'],
                    null,
                    null,
                ]);
            }
        }

        return $filas;
    }
}

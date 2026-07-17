<?php

namespace App\Exports;

use App\Services\ServicioRentabilidad;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

/**
 * Reporte de ganancias del mes (solo Admin): una fila por venta/desguace
 * con la ganancia calculada, más una fila de totales.
 */
class GananciasExport implements FromCollection, ShouldAutoSize, WithHeadings
{
    public function __construct(private string $mes) {}

    public function headings(): array
    {
        return [
            'Vehículo', 'VIN', 'Tipo', 'Fecha',
            'Precio de compra', 'Gastos', 'Recuperado', 'Ganancia',
        ];
    }

    public function collection(): Collection
    {
        $inicio = Carbon::createFromFormat('Y-m', $this->mes)->startOfMonth();
        $fin = $inicio->copy()->endOfMonth();

        $salidas = app(ServicioRentabilidad::class)->salidas($inicio, $fin);

        $filas = $salidas->map(fn (array $salida) => [
            $salida['vehiculo']->nombreCompleto(),
            $salida['vehiculo']->vin,
            $salida['tipo'],
            $salida['fecha']->format('d/m/Y'),
            $salida['compra'],
            $salida['gastos'],
            $salida['recuperado'],
            $salida['ganancia'],
        ]);

        if ($filas->isNotEmpty()) {
            $filas->push([
                'TOTAL '.$inicio->translatedFormat('F Y'), '', '', '',
                round($salidas->sum('compra'), 2),
                round($salidas->sum('gastos'), 2),
                round($salidas->sum('recuperado'), 2),
                round($salidas->sum('ganancia'), 2),
            ]);
        }

        return $filas;
    }
}

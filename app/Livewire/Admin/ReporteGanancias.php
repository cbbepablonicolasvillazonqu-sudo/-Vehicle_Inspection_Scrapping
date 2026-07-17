<?php

namespace App\Livewire\Admin;

use App\Services\ServicioRentabilidad;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Reporte de rentabilidad (solo Admin): ganancia por vehículo del mes
 * seleccionado, total del mes y acumulada. Exportable a Excel.
 */
#[Layout('layouts.app')]
#[Title('Reporte de ganancias')]
class ReporteGanancias extends Component
{
    /** Mes seleccionado en formato YYYY-MM. */
    #[Url(as: 'mes')]
    public string $mes = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->can('ver ganancias'), 403);

        if (! preg_match('/^\d{4}-\d{2}$/', $this->mes)) {
            $this->mes = now()->format('Y-m');
        }
    }

    public function render()
    {
        $rentabilidad = app(ServicioRentabilidad::class);

        $inicio = Carbon::createFromFormat('Y-m', $this->mes)->startOfMonth();
        $fin = $inicio->copy()->endOfMonth();

        $salidas = $rentabilidad->salidas($inicio, $fin);

        return view('livewire.admin.reporte-ganancias', [
            'salidas' => $salidas,
            'totalMes' => round($salidas->sum('ganancia'), 2),
            'totalRecuperado' => round($salidas->sum('recuperado'), 2),
            'totalInvertidoMes' => round($salidas->sum(fn ($s) => $s['compra'] + $s['gastos']), 2),
            'acumulada' => $rentabilidad->gananciaAcumulada(),
            'invertidoInventario' => $rentabilidad->totalInvertidoInventario(),
            'nombreMes' => $inicio->translatedFormat('F Y'),
        ]);
    }
}

<?php

namespace App\Exports;

use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 * Exportación de la lista de vehículos (XLSX o CSV).
 *
 * Respeta los permisos del usuario: el alcance por rol (visiblePara) y
 * las columnas sensibles (precio de compra, gastos y ganancia).
 */
class VehiculosExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
{
    public function __construct(
        private User $usuario,
        private string $busqueda = '',
        private string $estado = '',
    ) {}

    public function collection(): Collection
    {
        return Vehicle::query()
            ->visiblePara($this->usuario)
            ->buscar($this->busqueda)
            ->when($this->estado !== '', fn ($q) => $q->where('estado', $this->estado))
            ->withSum('gastos as gastos_total', 'monto')
            ->with(['venta', 'desguace'])
            ->latest()
            ->get();
    }

    public function headings(): array
    {
        $columnas = [
            __('Marca'), __('Modelo'), __('Año'), __('VIN'), __('Millas'),
            __('Estado'), __('Título'), __('Dónde está'), __('Fecha de compra'),
        ];

        if ($this->usuario->can('ver precios compra')) {
            $columnas[] = __('Precio de compra');
        }

        if ($this->usuario->can('registrar gastos')) {
            $columnas[] = __('Gastos');
        }

        $columnas[] = __('Recuperado (venta/desguace)');

        if ($this->usuario->can('ver ganancias')) {
            $columnas[] = __('Ganancia');
        }

        $columnas[] = __('Notas');

        return $columnas;
    }

    /** @param  Vehicle  $vehiculo */
    public function map($vehiculo): array
    {
        $gastos = (float) ($vehiculo->gastos_total ?? 0);

        // Una salida sin monto cargado queda vacía, nunca en 0: valorarla en 0
        // la convertiría en una pérdida ficticia. Vale tanto para el Junk car
        // sin monto como para la venta sin precio.
        $recuperado = match (true) {
            $vehiculo->venta?->precio_venta !== null => (float) $vehiculo->venta->precio_venta,
            $vehiculo->desguace?->monto_recibido !== null => (float) $vehiculo->desguace->monto_recibido,
            default => null,
        };

        $fila = [
            $vehiculo->marca,
            $vehiculo->modelo,
            $vehiculo->anio,
            $vehiculo->vin,
            $vehiculo->millas,
            $vehiculo->estado->etiqueta(),
            $vehiculo->estado_titulo?->etiqueta(),
            $vehiculo->ubicacion_destino?->etiqueta(),
            $vehiculo->fecha_compra?->format('d/m/Y'),
        ];

        if ($this->usuario->can('ver precios compra')) {
            $fila[] = (float) $vehiculo->precio_compra;
        }

        if ($this->usuario->can('registrar gastos')) {
            $fila[] = $gastos;
        }

        $fila[] = $recuperado;

        if ($this->usuario->can('ver ganancias')) {
            $fila[] = ($recuperado === null || $vehiculo->precio_compra === null)
                ? null
                : round($recuperado - (float) $vehiculo->precio_compra - $gastos, 2);
        }

        $fila[] = $vehiculo->notas;

        return $fila;
    }
}

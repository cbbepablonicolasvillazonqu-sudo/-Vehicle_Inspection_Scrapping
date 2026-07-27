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
        $columnas = ['Marca', 'Modelo', 'Año', 'VIN', 'Millas', 'Estado', 'Título', 'Dónde está', 'Fecha de compra'];

        if ($this->usuario->can('ver precios compra')) {
            $columnas[] = 'Precio de compra';
        }

        if ($this->usuario->can('registrar gastos')) {
            $columnas[] = 'Gastos';
        }

        $columnas[] = 'Recuperado (venta/desguace)';

        if ($this->usuario->can('ver ganancias')) {
            $columnas[] = 'Ganancia';
        }

        $columnas[] = 'Notas';

        return $columnas;
    }

    /** @param  Vehicle  $vehiculo */
    public function map($vehiculo): array
    {
        $gastos = (float) ($vehiculo->gastos_total ?? 0);

        // Un Junk car sin monto cargado queda vacío, nunca en 0: valorarlo en 0
        // lo convertiría en una pérdida ficticia.
        $recuperado = match (true) {
            $vehiculo->venta !== null => (float) $vehiculo->venta->precio_venta,
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

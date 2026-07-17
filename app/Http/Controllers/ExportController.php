<?php

namespace App\Http\Controllers;

use App\Exports\GananciasExport;
use App\Exports\VehiculosExport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Excel as TipoEscritor;
use Maatwebsite\Excel\Facades\Excel;

class ExportController extends Controller
{
    /**
     * Lista de vehículos en XLSX o CSV, respetando los filtros activos
     * y los permisos del usuario.
     */
    public function vehiculos(Request $request, string $formato)
    {
        abort_unless(in_array($formato, ['xlsx', 'csv'], true), 404);

        $export = new VehiculosExport(
            $request->user(),
            (string) $request->query('buscar', ''),
            (string) $request->query('estado', ''),
        );

        $nombre = 'vehiculos-'.now()->format('Y-m-d').'.'.$formato;

        return Excel::download(
            $export,
            $nombre,
            $formato === 'csv' ? TipoEscritor::CSV : TipoEscritor::XLSX,
        );
    }

    /**
     * Reporte de ganancias del mes (solo Admin, protegido por ruta).
     */
    public function ganancias(Request $request)
    {
        $mes = (string) $request->query('mes', now()->format('Y-m'));

        if (! preg_match('/^\d{4}-\d{2}$/', $mes)) {
            $mes = now()->format('Y-m');
        }

        return Excel::download(
            new GananciasExport($mes),
            "ganancias-{$mes}.xlsx",
        );
    }
}

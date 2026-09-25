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
    /** Ventana y tope de la lista "Recogidos" del panel del Gruero. */
    private const RECOGIDOS_DIAS = 30;

    private const RECOGIDOS_LIMITE = 20;

    /**
     * Panel del Gruero, agrupado por lo que tiene que hacer:
     * por recoger → recogidos → Junk car por completar → Junk car listos.
     */
    private function panelGruero(callable $base)
    {
        $activos = fn () => $base()->where('estado', '!=', EstadoVehiculo::Desguace->value);
        $enJunk = fn () => $base()->where('estado', EstadoVehiculo::Desguace->value);

        // "Recogidos" es el acuse de su trabajo reciente, no un archivo histórico:
        // se acota a los últimos días y con tope, para que el celular no cargue
        // cientos de vehículos. El historial completo está en /vehiculos.
        $recientes = fn () => $activos()
            ->whereNotNull('metodo_pago_gruero')
            ->where(fn ($q) => $q->whereNull('fecha_compra')
                ->orWhereDate('fecha_compra', '>=', now()->subDays(self::RECOGIDOS_DIAS)));

        return view('livewire.panel-gruero', [
            // Todavía no registró el recojo.
            'porRecoger' => $activos()->whereNull('metodo_pago_gruero')->latest()->get(),

            // Ya registró pago, destino, titulación y monto (solo los recientes).
            'recogidos' => $recientes()->latest()->limit(self::RECOGIDOS_LIMITE)->get(),
            'recogidosTotal' => $recientes()->count(),
            'recogidosDias' => self::RECOGIDOS_DIAS,

            // En Junk car pero falta el catalizador o el monto pagado.
            'junkPorCompletar' => $enJunk()
                ->where(fn ($q) => $q->whereNull('tiene_catalizador')
                    ->orWhereDoesntHave('desguace')
                    ->orWhereHas('desguace', fn ($d) => $d->whereNull('monto_recibido')))
                ->latest()->get(),

            // En Junk car con todos los datos cargados.
            'junkCompletados' => $enJunk()
                ->whereNotNull('tiene_catalizador')
                ->whereHas('desguace', fn ($d) => $d->whereNotNull('monto_recibido'))
                ->with('desguace')
                ->latest()->get(),
        ]);
    }

    public function render()
    {
        $usuario = auth()->user();

        $base = fn () => Vehicle::query()->visiblePara($usuario);

        // El Gruero tiene su propio panel: lo que le falta recoger, lo que ya
        // recogió y los Junk car pendientes de completar.
        if ($usuario->hasRole('gruero')) {
            return $this->panelGruero($base);
        }

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
        // - Mecánico: sin "Publicados", "Vendidos este mes" ni "Junk car".
        // - Vendedor: sin "En reparación" ni "Junk car".
        // - Gruero: tiene su propio panel (ver panelGruero).
        $ocultas = match (true) {
            $usuario->hasRole('mecanico') => ['publicados', 'vendidosMes', 'desguace'],
            $usuario->hasRole('vendedor') => ['reparacion', 'desguace'],
            $usuario->hasRole('gruero') => ['reparacion', 'listos', 'publicados', 'vendidosMes'],
            default => [],
        };

        $tarjetas = collect([
            ['clave' => 'inventario', 'txt' => __('En inventario'), 'icono' => 'archivo', 'color' => 'slate', 'estado' => null],
            ['clave' => 'reparacion', 'txt' => __('En reparación'), 'icono' => 'llave-inglesa', 'color' => 'yellow', 'estado' => 'en_reparacion'],
            ['clave' => 'listos', 'txt' => __('Listos'), 'icono' => 'check', 'color' => 'green', 'estado' => 'listo'],
            ['clave' => 'publicados', 'txt' => __('Vendidos'), 'icono' => 'etiqueta', 'color' => 'sky', 'estado' => 'publicado'],
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
                'pendientes' => $rentabilidad->contarPendientesDeValorar(),
            ];
        }

        return view('livewire.dashboard', [
            'conteos' => $conteos,
            'tarjetas' => $tarjetas,
            'finanzas' => $finanzas,
        ]);
    }
}

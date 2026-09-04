<?php

namespace App\Livewire\Vehiculos;

use App\Enums\EstadoVehiculo;
use App\Models\Vehicle;
use App\Services\ServicioEstadoVehiculo;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Botones de transición de estado + historial de estados del vehículo.
 * Las transiciones permitidas dependen del rol (ServicioEstadoVehiculo).
 */
class GestorEstado extends Component
{
    public Vehicle $vehiculo;

    public string $nota = '';

    public function mount(Vehicle $vehiculo): void
    {
        $this->vehiculo = $vehiculo;
    }

    #[On('vehiculo-actualizado')]
    public function refrescar(): void
    {
        $this->vehiculo->refresh();
    }

    public function cambiarEstado(string $estado): void
    {
        $nuevo = EstadoVehiculo::from($estado);

        app(ServicioEstadoVehiculo::class)->cambiar(
            auth()->user(),
            $this->vehiculo,
            $nuevo,
            filled($this->nota) ? $this->nota : null,
        );

        $this->nota = '';
        $this->dispatch('vehiculo-actualizado');
        $this->dispatch('notificar', mensaje: __('Estado: :estado', ['estado' => $nuevo->etiquetaCorta()]));
    }

    public function render()
    {
        return view('livewire.vehiculos.gestor-estado', [
            'transiciones' => app(ServicioEstadoVehiculo::class)
                ->transicionesPermitidas(auth()->user(), $this->vehiculo),
            'historial' => $this->vehiculo->historialEstados()
                ->with('usuario')
                ->latest()
                ->latest('id')
                ->get(),
        ]);
    }
}

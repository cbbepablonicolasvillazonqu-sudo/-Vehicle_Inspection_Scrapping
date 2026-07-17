<?php

namespace App\Livewire\Vehiculos;

use App\Models\Vehicle;
use App\Services\ServicioAuditoria;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Ficha completa del vehículo: datos, estado, fotos, gastos, venta,
 * desguace y auditoría (secciones según permisos del rol).
 */
#[Layout('layouts.app')]
class FichaVehiculo extends Component
{
    use AuthorizesRequests;

    public Vehicle $vehiculo;

    public function mount(Vehicle $vehiculo): void
    {
        $this->authorize('view', $vehiculo);
        $this->vehiculo = $vehiculo;
    }

    #[On('vehiculo-actualizado')]
    public function refrescar(): void
    {
        $this->vehiculo->refresh();
    }

    public function eliminar(): mixed
    {
        $this->authorize('delete', $this->vehiculo);

        app(ServicioAuditoria::class)->registrar($this->vehiculo, 'vehiculo_eliminado', [
            'vin' => $this->vehiculo->vin,
        ]);

        $this->vehiculo->delete();

        session()->flash('ok', 'Vehículo eliminado.');

        return $this->redirectRoute('vehiculos.index', navigate: false);
    }

    public function render()
    {
        $auditoria = auth()->user()->hasRole('admin')
            ? $this->vehiculo->auditoria()->with('usuario')->latest()->limit(100)->get()
            : collect();

        return view('livewire.vehiculos.ficha-vehiculo', [
            'auditoria' => $auditoria,
        ])->title($this->vehiculo->nombreCompleto());
    }
}

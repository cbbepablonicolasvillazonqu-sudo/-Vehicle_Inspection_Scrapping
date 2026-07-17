<?php

namespace App\Livewire\Vehiculos;

use App\Enums\EstadoVehiculo;
use App\Models\Vehicle;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Listado de vehículos con búsqueda (marca/modelo/VIN) y filtro por estado.
 * El alcance depende del rol (scope visiblePara).
 */
#[Layout('layouts.app')]
#[Title('Vehículos')]
class ListaVehiculos extends Component
{
    use AuthorizesRequests, WithPagination;

    #[Url(as: 'buscar', except: '')]
    public string $busqueda = '';

    #[Url(as: 'estado', except: '')]
    public string $filtroEstado = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Vehicle::class);
    }

    public function updatedBusqueda(): void
    {
        $this->resetPage();
    }

    public function updatedFiltroEstado(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $vehiculos = Vehicle::query()
            ->visiblePara(auth()->user())
            ->buscar($this->busqueda)
            ->when($this->filtroEstado !== '', fn ($q) => $q->where('estado', $this->filtroEstado))
            ->withCount('fotos')
            ->latest()
            ->paginate(12);

        return view('livewire.vehiculos.lista-vehiculos', [
            'vehiculos' => $vehiculos,
            'estados' => EstadoVehiculo::cases(),
        ]);
    }
}

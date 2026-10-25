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

    /** true cuando se incrusta dentro del panel (evita duplicar el padding). */
    public bool $embebido = false;

    public function mount(): void
    {
        $this->authorize('viewAny', Vehicle::class);

        // La tarjeta del panel y los enlaces viejos llegan con ?estado=publicado,
        // que ya no es una opción del filtro: sin esto el desplegable diría
        // "Todos los estados" con la lista filtrada.
        if ($this->filtroEstado === EstadoVehiculo::Publicado->value) {
            $this->filtroEstado = EstadoVehiculo::Vendido->value;
        }
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
            ->conEstado($this->filtroEstado)
            ->withCount('fotos')
            ->with('fotoPortada')
            ->latest()
            ->paginate(12);

        return view('livewire.vehiculos.lista-vehiculos', [
            'vehiculos' => $vehiculos,
            'estados' => EstadoVehiculo::opcionesFiltro(),
        ]);
    }
}

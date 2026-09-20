<?php

namespace App\Livewire\Vehiculos;

use App\Enums\EstadoVehiculo;
use App\Models\ScrapRecord;
use App\Models\Vehicle;
use App\Services\ServicioAuditoria;
use App\Services\ServicioEstadoVehiculo;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Envío masivo de vehículos a Junk car: búsqueda con filtros y selección
 * múltiple. Solo Admin: es quien decide qué vehículo va a Junk car.
 *
 * El monto recibido y la empresa se completan después, desde la ficha.
 */
#[Layout('layouts.app')]
#[Title('Enviar a Junk car')]
class EnvioJunkCar extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $buscar = '';

    #[Url]
    public string $estado = '';

    /** @var array<int, int> IDs seleccionados */
    public array $seleccion = [];

    public function mount(): void
    {
        abort_unless(auth()->user()->can('enviar a junk'), 403);
    }

    public function updatedBuscar(): void
    {
        $this->resetPage();
    }

    public function updatedEstado(): void
    {
        $this->resetPage();
    }

    /** Vehículos candidatos: los visibles para el usuario que aún no son finales. */
    protected function consulta()
    {
        return Vehicle::query()
            ->visiblePara(auth()->user())
            ->buscar($this->buscar)
            ->when($this->estado !== '', fn ($q) => $q->where('estado', $this->estado))
            ->whereNotIn('estado', [EstadoVehiculo::Vendido, EstadoVehiculo::Desguace])
            ->latest();
    }

    /** Marca o desmarca todos los resultados de la página actual. */
    public function alternarPagina(array $ids): void
    {
        $todos = array_diff($ids, $this->seleccion) === [];

        $this->seleccion = $todos
            ? array_values(array_diff($this->seleccion, $ids))
            : array_values(array_unique(array_merge($this->seleccion, $ids)));
    }

    public function limpiarSeleccion(): void
    {
        $this->reset('seleccion');
    }

    public function enviar(): void
    {
        abort_unless(auth()->user()->can('enviar a junk'), 403);

        if ($this->seleccion === []) {
            $this->dispatch('notificar', mensaje: __('No hay vehículos seleccionados'));

            return;
        }

        // Solo IDs realmente visibles para este usuario y aún no finalizados.
        $vehiculos = Vehicle::query()
            ->visiblePara(auth()->user())
            ->whereIn('id', $this->seleccion)
            ->whereNotIn('estado', [EstadoVehiculo::Vendido, EstadoVehiculo::Desguace])
            ->get();

        $servicio = app(ServicioEstadoVehiculo::class);
        $auditoria = app(ServicioAuditoria::class);
        $enviados = 0;

        DB::transaction(function () use ($vehiculos, $servicio, $auditoria, &$enviados) {
            foreach ($vehiculos as $vehiculo) {
                ScrapRecord::create([
                    'vehicle_id' => $vehiculo->id,
                    'fecha' => now()->toDateString(),
                    'user_id' => auth()->id(),
                ]);

                $servicio->cambiar(
                    auth()->user(),
                    $vehiculo,
                    EstadoVehiculo::Desguace,
                    // Sin __(): la nota se guarda en la base y se traduce al mostrarla.
                    // Traducirla aquí dejaría filas en el idioma de quien apretó el botón.
                    'Envío masivo a Junk car',
                    interno: true,
                );

                $auditoria->registrar($vehiculo, 'enviado_a_junk', ['masivo' => true]);
                $enviados++;
            }
        });

        $this->reset('seleccion');
        $this->resetPage();

        $this->dispatch('notificar', mensaje: $enviados === 1
            ? __('1 vehículo enviado a Junk car')
            : __(':n vehículos enviados a Junk car', ['n' => $enviados]));
    }

    /**
     * Seleccionados que el filtro actual no muestra.
     *
     * La selección sobrevive a los cambios de búsqueda a propósito: borrarla
     * sería tirar el trabajo del usuario. Pero enviar a Junk car es
     * irreversible, así que hay que decirle cuántos se irían sin que los vea.
     */
    private function fueraDelFiltro(): int
    {
        if ($this->seleccion === []) {
            return 0;
        }

        return count($this->seleccion) - $this->consulta()->whereIn('id', $this->seleccion)->count();
    }

    public function render()
    {
        $vehiculos = $this->consulta()->paginate(15);

        return view('livewire.vehiculos.envio-junk-car', [
            'vehiculos' => $vehiculos,
            'fueraDelFiltro' => $this->fueraDelFiltro(),
            'idsPagina' => $vehiculos->pluck('id')->all(),
            'estados' => collect(EstadoVehiculo::cases())
                ->reject(fn ($e) => $e->esFinal())
                ->mapWithKeys(fn ($e) => [$e->value => $e->etiquetaCorta()])
                ->all(),
        ]);
    }
}

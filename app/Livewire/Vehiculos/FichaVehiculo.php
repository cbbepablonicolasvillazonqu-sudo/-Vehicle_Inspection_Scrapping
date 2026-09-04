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

    /** Editor inline del precio de venta sugerido (solo Admin). */
    public string $precioSugerido = '';

    public function mount(Vehicle $vehiculo): void
    {
        $this->authorize('view', $vehiculo);
        $this->vehiculo = $vehiculo;
        $this->precioSugerido = $vehiculo->precio_sugerido !== null
            ? (string) $vehiculo->precio_sugerido
            : '';
    }

    #[On('vehiculo-actualizado')]
    public function refrescar(): void
    {
        $this->vehiculo->refresh();
    }

    /**
     * Fija (o quita, si se deja vacío) el precio de venta sugerido.
     * Referencia del Vendedor al negociar; solo Admin puede fijarlo.
     */
    public function guardarPrecioSugerido(): void
    {
        abort_unless(auth()->user()->can('fijar precio venta'), 403);

        $datos = $this->validate(
            ['precioSugerido' => ['nullable', 'numeric', 'min:0', 'max:9999999']],
            [],
            ['precioSugerido' => __('precio de venta sugerido')],
        );

        $anterior = $this->vehiculo->precio_sugerido;
        $nuevo = filled($datos['precioSugerido'])
            ? number_format(round((float) $datos['precioSugerido'], 2), 2, '.', '')
            : null;

        $this->vehiculo->forceFill(['precio_sugerido' => $nuevo])->save();

        app(ServicioAuditoria::class)->registrar($this->vehiculo, 'precio_sugerido_actualizado', [
            'antes' => $anterior !== null ? (string) $anterior : null,
            'despues' => $nuevo,
        ]);

        $this->dispatch('vehiculo-actualizado');
        $this->dispatch('notificar', mensaje: __('Precio sugerido guardado'));
    }

    public function eliminar(): mixed
    {
        $this->authorize('delete', $this->vehiculo);

        app(ServicioAuditoria::class)->registrar($this->vehiculo, 'vehiculo_eliminado', [
            'vin' => $this->vehiculo->vin,
        ]);

        $this->vehiculo->delete();

        session()->flash('ok', __('Vehículo eliminado.'));

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

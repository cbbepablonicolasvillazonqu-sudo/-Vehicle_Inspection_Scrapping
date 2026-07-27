<?php

namespace App\Livewire\Vehiculos;

use App\Models\Vehicle;
use App\Services\ServicioAuditoria;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Datos del Junk car: si el vehículo tiene catalizador y cuánto pagaron por él.
 *
 * El Admin decide qué vehículo va a Junk car; este formulario lo completa
 * el Admin o el Gruero que tiene el vehículo asignado (es quien lo ve).
 */
class GestorJunkCar extends Component
{
    public Vehicle $vehiculo;

    public string $tiene_catalizador = '';

    public string $monto_junk = '';

    public function mount(Vehicle $vehiculo): void
    {
        $this->vehiculo = $vehiculo;
        $this->sincronizar();
    }

    private function sincronizar(): void
    {
        $this->tiene_catalizador = $this->vehiculo->tiene_catalizador === null
            ? ''
            : ($this->vehiculo->tiene_catalizador ? '1' : '0');

        $monto = $this->vehiculo->desguace?->monto_recibido;
        $this->monto_junk = $monto !== null ? (string) $monto : '';
    }

    #[On('vehiculo-actualizado')]
    public function refrescar(): void
    {
        $this->vehiculo->refresh();
        $this->sincronizar();
    }

    /** Solo se muestra cuando el vehículo ya fue enviado a Junk car. */
    public function esJunkCar(): bool
    {
        return $this->vehiculo->desguace !== null;
    }

    public function puedeCompletar(): bool
    {
        $usuario = auth()->user();

        if (! $usuario->can('completar junk')) {
            return false;
        }

        return $usuario->hasRole('admin') || $this->vehiculo->asignado_a === $usuario->id;
    }

    protected function rules(): array
    {
        return [
            'tiene_catalizador' => ['required', 'in:0,1'],
            'monto_junk' => ['required', 'numeric', 'min:0', 'max:9999999'],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'tiene_catalizador' => __('catalizador'),
            'monto_junk' => __('monto pagado por el Junk car'),
        ];
    }

    public function guardar(): void
    {
        abort_unless($this->esJunkCar() && $this->puedeCompletar(), 403);

        $datos = $this->validate();
        $monto = number_format(round((float) $datos['monto_junk'], 2), 2, '.', '');

        DB::transaction(function () use ($datos, $monto) {
            $this->vehiculo->forceFill([
                'tiene_catalizador' => (bool) $datos['tiene_catalizador'],
            ])->save();

            $this->vehiculo->desguace->update(['monto_recibido' => $monto]);

            app(ServicioAuditoria::class)->registrar($this->vehiculo, 'junk_car_completado', [
                'catalizador' => (bool) $datos['tiene_catalizador'],
                'monto' => $monto,
            ]);
        });

        $this->dispatch('vehiculo-actualizado');
        $this->dispatch('notificar', mensaje: __('Datos del Junk car guardados'));
    }

    public function render()
    {
        return view('livewire.vehiculos.gestor-junk-car');
    }
}

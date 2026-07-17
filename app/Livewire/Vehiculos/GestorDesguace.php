<?php

namespace App\Livewire\Vehiculos;

use App\Enums\EstadoVehiculo;
use App\Models\Vehicle;
use App\Services\ServicioAuditoria;
use App\Services\ServicioEstadoVehiculo;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Desguace del vehículo (solo Admin): fecha, monto recibido, empresa y notas.
 * Eliminar el desguace revierte el estado anterior (según historial).
 */
class GestorDesguace extends Component
{
    public Vehicle $vehiculo;

    public bool $editando = false;

    public string $fecha = '';

    public string $monto_recibido = '';

    public string $empresa = '';

    public string $notas = '';

    public function mount(Vehicle $vehiculo): void
    {
        $this->vehiculo = $vehiculo;
        $this->fecha = now()->format('Y-m-d');
    }

    #[On('vehiculo-actualizado')]
    public function refrescar(): void
    {
        $this->vehiculo->refresh();
    }

    protected function rules(): array
    {
        return [
            'fecha' => ['required', 'date', 'before_or_equal:today'],
            'monto_recibido' => ['required', 'numeric', 'min:0', 'max:9999999'],
            'empresa' => ['required', 'string', 'max:120'],
            'notas' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function puedeGestionar(): bool
    {
        return auth()->user()->can('registrar desguace');
    }

    public function puedeEnviar(): bool
    {
        return $this->puedeGestionar()
            && $this->vehiculo->desguace === null
            && ! $this->vehiculo->estado->esFinal();
    }

    public function registrar(): void
    {
        abort_unless($this->puedeEnviar(), 403);

        $datos = $this->validate();
        $datos['notas'] = filled($datos['notas']) ? $datos['notas'] : null;

        DB::transaction(function () use ($datos) {
            $this->vehiculo->desguace()->create($datos + ['user_id' => auth()->id()]);

            app(ServicioEstadoVehiculo::class)->cambiar(
                auth()->user(),
                $this->vehiculo,
                EstadoVehiculo::Desguace,
                'Enviado a desguace',
                interno: true,
            );

            app(ServicioAuditoria::class)->registrar($this->vehiculo, 'desguace_registrado', [
                'empresa' => $datos['empresa'],
                'monto' => $datos['monto_recibido'],
            ]);
        });

        $this->dispatch('vehiculo-actualizado');
        $this->dispatch('notificar', mensaje: 'Vehículo enviado a desguace');
    }

    public function editar(): void
    {
        abort_unless($this->puedeGestionar() && $this->vehiculo->desguace, 403);

        $desguace = $this->vehiculo->desguace;

        $this->resetValidation();
        $this->fecha = $desguace->fecha->format('Y-m-d');
        $this->monto_recibido = (string) $desguace->monto_recibido;
        $this->empresa = $desguace->empresa;
        $this->notas = (string) $desguace->notas;
        $this->editando = true;
    }

    public function actualizar(): void
    {
        abort_unless($this->puedeGestionar() && $this->vehiculo->desguace, 403);

        $datos = $this->validate();
        $datos['notas'] = filled($datos['notas']) ? $datos['notas'] : null;

        $desguace = $this->vehiculo->desguace;
        $anterior = ['empresa' => $desguace->empresa, 'monto' => (string) $desguace->monto_recibido];

        $desguace->update($datos);

        app(ServicioAuditoria::class)->registrar($this->vehiculo, 'desguace_editado', [
            'antes' => $anterior,
            'despues' => ['empresa' => $datos['empresa'], 'monto' => $datos['monto_recibido']],
        ]);

        $this->editando = false;
        $this->dispatch('vehiculo-actualizado');
        $this->dispatch('notificar', mensaje: 'Desguace actualizado');
    }

    public function cancelarEdicion(): void
    {
        $this->editando = false;
    }

    public function eliminarDesguace(): void
    {
        abort_unless($this->puedeGestionar() && $this->vehiculo->desguace, 403);

        DB::transaction(function () {
            $desguace = $this->vehiculo->desguace;

            $ultimoCambio = $this->vehiculo->historialEstados()
                ->where('estado_nuevo', EstadoVehiculo::Desguace->value)
                ->latest('id')
                ->first();

            $estadoPrevio = $ultimoCambio?->estado_anterior ?? EstadoVehiculo::Comprado;

            app(ServicioAuditoria::class)->registrar($this->vehiculo, 'desguace_eliminado', [
                'empresa' => $desguace->empresa,
                'monto' => (string) $desguace->monto_recibido,
            ]);

            $desguace->delete();

            app(ServicioEstadoVehiculo::class)->cambiar(
                auth()->user(),
                $this->vehiculo,
                $estadoPrevio,
                'Desguace eliminado por Admin',
                interno: true,
            );
        });

        $this->dispatch('vehiculo-actualizado');
        $this->dispatch('notificar', mensaje: 'Desguace eliminado; estado revertido');
    }

    public function render()
    {
        return view('livewire.vehiculos.gestor-desguace', [
            'desguace' => $this->vehiculo->desguace?->load('usuario'),
        ]);
    }
}

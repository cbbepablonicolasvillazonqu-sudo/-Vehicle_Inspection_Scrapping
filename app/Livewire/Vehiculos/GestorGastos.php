<?php

namespace App\Livewire\Vehiculos;

use App\Enums\CategoriaGasto;
use App\Models\Expense;
use App\Models\Vehicle;
use App\Services\ServicioAuditoria;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Gastos del vehículo: categoría, descripción, monto, fecha y quién lo
 * registró. Editar/eliminar: Admin siempre; el autor mientras el vehículo
 * no esté bloqueado.
 */
class GestorGastos extends Component
{
    public Vehicle $vehiculo;

    public ?int $gastoId = null;

    public string $categoria = '';

    public string $descripcion = '';

    public string $monto = '';

    public string $fecha = '';

    public bool $mostrandoFormulario = false;

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
            'categoria' => ['required', Rule::enum(CategoriaGasto::class)],
            'descripcion' => ['required', 'string', 'max:200'],
            'monto' => ['required', 'numeric', 'min:0.01', 'max:9999999'],
            'fecha' => ['required', 'date', 'before_or_equal:today'],
        ];
    }

    public function puedeRegistrar(): bool
    {
        $usuario = auth()->user();

        if (! $usuario->can('registrar gastos')) {
            return false;
        }

        return $usuario->hasRole('admin') || ! $this->vehiculo->estaBloqueado();
    }

    private function puedeModificar(Expense $gasto): bool
    {
        $usuario = auth()->user();

        return $usuario->hasRole('admin')
            || ($gasto->user_id === $usuario->id && $this->puedeRegistrar());
    }

    public function nuevo(): void
    {
        abort_unless($this->puedeRegistrar(), 403);

        $this->resetValidation();
        $this->reset(['gastoId', 'categoria', 'descripcion', 'monto']);
        $this->fecha = now()->format('Y-m-d');
        $this->mostrandoFormulario = true;
    }

    public function editar(int $id): void
    {
        $gasto = $this->vehiculo->gastos()->findOrFail($id);
        abort_unless($this->puedeModificar($gasto), 403);

        $this->resetValidation();
        $this->gastoId = $gasto->id;
        $this->categoria = $gasto->categoria->value;
        $this->descripcion = $gasto->descripcion;
        $this->monto = (string) $gasto->monto;
        $this->fecha = $gasto->fecha->format('Y-m-d');
        $this->mostrandoFormulario = true;
    }

    public function guardar(): void
    {
        $datos = $this->validate();
        $auditoria = app(ServicioAuditoria::class);

        if ($this->gastoId) {
            $gasto = $this->vehiculo->gastos()->findOrFail($this->gastoId);
            abort_unless($this->puedeModificar($gasto), 403);

            $anterior = ['monto' => (string) $gasto->monto, 'descripcion' => $gasto->descripcion];
            $gasto->update($datos);

            $auditoria->registrar($this->vehiculo, 'gasto_editado', [
                'antes' => $anterior,
                'despues' => ['monto' => $datos['monto'], 'descripcion' => $datos['descripcion']],
            ]);

            $mensaje = 'Gasto actualizado';
        } else {
            abort_unless($this->puedeRegistrar(), 403);

            $this->vehiculo->gastos()->create($datos + ['user_id' => auth()->id()]);

            $auditoria->registrar($this->vehiculo, 'gasto_registrado', [
                'categoria' => CategoriaGasto::from($datos['categoria'])->etiqueta(),
                'monto' => $datos['monto'],
                'descripcion' => $datos['descripcion'],
            ]);

            $mensaje = 'Gasto registrado';
        }

        $this->mostrandoFormulario = false;
        $this->reset(['gastoId', 'categoria', 'descripcion', 'monto']);
        $this->fecha = now()->format('Y-m-d');

        $this->dispatch('vehiculo-actualizado');
        $this->dispatch('notificar', mensaje: $mensaje);
    }

    public function cancelar(): void
    {
        $this->mostrandoFormulario = false;
    }

    public function eliminar(int $id): void
    {
        $gasto = $this->vehiculo->gastos()->findOrFail($id);
        abort_unless($this->puedeModificar($gasto), 403);

        app(ServicioAuditoria::class)->registrar($this->vehiculo, 'gasto_eliminado', [
            'categoria' => $gasto->categoria->etiqueta(),
            'monto' => (string) $gasto->monto,
            'descripcion' => $gasto->descripcion,
        ]);

        $gasto->delete();

        $this->dispatch('vehiculo-actualizado');
        $this->dispatch('notificar', mensaje: 'Gasto eliminado');
    }

    public function render()
    {
        return view('livewire.vehiculos.gestor-gastos', [
            'gastos' => $this->vehiculo->gastos()->with('usuario')->orderByDesc('fecha')->orderByDesc('id')->get(),
            'categorias' => CategoriaGasto::opciones(),
            'total' => $this->vehiculo->totalGastos(),
        ]);
    }
}

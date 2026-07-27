<?php

namespace App\Livewire\Vehiculos;

use App\Enums\CategoriaGasto;
use App\Models\Expense;
use App\Models\Vehicle;
use App\Services\ServicioAuditoria;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Gastos del vehículo: categoría, descripción, monto, fecha, fotos y quién lo
 * registró. Editar/eliminar: Admin siempre; el autor mientras el vehículo
 * no esté bloqueado.
 *
 * Es el único módulo de la app con carga de fotos.
 */
class GestorGastos extends Component
{
    use WithFileUploads;

    public Vehicle $vehiculo;

    /** @var array<int, \Livewire\Features\SupportFileUploads\TemporaryUploadedFile> */
    public array $fotos = [];

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
            'fotos' => ['array', 'max:10'],
            'fotos.*' => ['image', 'max:10240'], // 10 MB por foto
        ];
    }

    /** Las fotos solo se cargan aquí, y solo si el rol tiene el permiso. */
    public function puedeSubirFotos(): bool
    {
        return auth()->user()->can('subir fotos') && $this->puedeRegistrar();
    }

    /** Quita una foto de la selección previa (antes de guardar el gasto). */
    public function quitarSeleccion(int $indice): void
    {
        unset($this->fotos[$indice]);
        $this->fotos = array_values($this->fotos);
        $this->resetValidation();
    }

    public function limpiarSeleccion(): void
    {
        $this->reset('fotos');
        $this->resetValidation();
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
        $this->reset(['gastoId', 'categoria', 'descripcion', 'monto', 'fotos']);
        $this->fecha = now()->format('Y-m-d');
        $this->mostrandoFormulario = true;
    }

    public function editar(int $id): void
    {
        $gasto = $this->vehiculo->gastos()->findOrFail($id);
        abort_unless($this->puedeModificar($gasto), 403);

        $this->resetValidation();
        $this->reset('fotos');
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
        $fotos = $datos['fotos'] ?? [];
        unset($datos['fotos']);

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

            $gasto = $this->vehiculo->gastos()->create($datos + ['user_id' => auth()->id()]);

            $auditoria->registrar($this->vehiculo, 'gasto_registrado', [
                'categoria' => CategoriaGasto::from($datos['categoria'])->etiqueta(),
                'monto' => $datos['monto'],
                'descripcion' => $datos['descripcion'],
            ]);

            $mensaje = 'Gasto registrado';
        }

        if ($fotos !== []) {
            $this->guardarFotos($gasto, $fotos, $auditoria);
        }

        $this->mostrandoFormulario = false;
        $this->reset(['gastoId', 'categoria', 'descripcion', 'monto', 'fotos']);
        $this->fecha = now()->format('Y-m-d');

        $this->dispatch('vehiculo-actualizado');
        $this->dispatch('notificar', mensaje: $mensaje);
    }

    /** Guarda las fotos del gasto en storage/app/public/vehiculos/{id}/gasto/. */
    private function guardarFotos(Expense $gasto, array $fotos, ServicioAuditoria $auditoria): void
    {
        abort_unless($this->puedeSubirFotos(), 403);

        foreach ($fotos as $foto) {
            $ruta = $foto->store("vehiculos/{$this->vehiculo->id}/gasto", 'public');

            $this->vehiculo->fotos()->create([
                'expense_id' => $gasto->id,
                'etapa' => 'gasto',
                'ruta' => $ruta,
                'nombre_original' => $foto->getClientOriginalName(),
                'user_id' => auth()->id(),
            ]);

            $auditoria->registrar($this->vehiculo, 'foto_subida', [
                'gasto' => $gasto->descripcion,
                'archivo' => $foto->getClientOriginalName(),
            ]);
        }
    }

    public function eliminarFoto(int $fotoId): void
    {
        $foto = $this->vehiculo->fotos()->whereNotNull('expense_id')->findOrFail($fotoId);
        $usuario = auth()->user();

        $puede = $usuario->hasRole('admin')
            || ($foto->user_id === $usuario->id && $this->puedeSubirFotos());

        abort_unless($puede, 403);

        Storage::disk('public')->delete($foto->ruta);

        app(ServicioAuditoria::class)->registrar($this->vehiculo, 'foto_eliminada', [
            'archivo' => $foto->nombre_original,
        ]);

        $foto->delete();

        $this->dispatch('notificar', mensaje: __('Foto eliminada'));
    }

    public function cancelar(): void
    {
        $this->mostrandoFormulario = false;
        $this->reset('fotos');
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
            'gastos' => $this->vehiculo->gastos()->with(['usuario', 'fotos'])->orderByDesc('fecha')->orderByDesc('id')->get(),
            'categorias' => CategoriaGasto::opciones(),
            'total' => $this->vehiculo->totalGastos(),
        ]);
    }
}

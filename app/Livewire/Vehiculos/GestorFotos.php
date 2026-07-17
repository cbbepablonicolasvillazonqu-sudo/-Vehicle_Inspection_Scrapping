<?php

namespace App\Livewire\Vehiculos;

use App\Enums\EtapaFoto;
use App\Models\Vehicle;
use App\Services\ServicioAuditoria;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Fotos del vehículo organizadas por etapa (compra / reparación / venta).
 * Se guardan en storage/app/public/vehiculos/{id}/{etapa}/.
 */
class GestorFotos extends Component
{
    use WithFileUploads;

    public Vehicle $vehiculo;

    /** Etapa seleccionada para subir/ver. */
    public string $etapa = 'compra';

    /** @var array<int, \Livewire\Features\SupportFileUploads\TemporaryUploadedFile> */
    public array $fotos = [];

    public function mount(Vehicle $vehiculo): void
    {
        $this->vehiculo = $vehiculo;
    }

    #[On('vehiculo-actualizado')]
    public function refrescar(): void
    {
        $this->vehiculo->refresh();
    }

    public function puedeGestionar(): bool
    {
        $usuario = auth()->user();

        if (! $usuario->can('subir fotos')) {
            return false;
        }

        // Vehículo vendido/desguazado: solo Admin puede modificar.
        return $usuario->hasRole('admin') || ! $this->vehiculo->estaBloqueado();
    }

    public function subir(): void
    {
        abort_unless($this->puedeGestionar(), 403);

        $this->validate([
            'etapa' => ['required', Rule::enum(EtapaFoto::class)],
            'fotos' => ['required', 'array', 'min:1', 'max:10'],
            'fotos.*' => ['image', 'max:5120'], // 5 MB por foto
        ]);

        $auditoria = app(ServicioAuditoria::class);

        foreach ($this->fotos as $foto) {
            $ruta = $foto->store("vehiculos/{$this->vehiculo->id}/{$this->etapa}", 'public');

            $this->vehiculo->fotos()->create([
                'etapa' => $this->etapa,
                'ruta' => $ruta,
                'nombre_original' => $foto->getClientOriginalName(),
                'user_id' => auth()->id(),
            ]);

            $auditoria->registrar($this->vehiculo, 'foto_subida', [
                'etapa' => $this->etapa,
                'archivo' => $foto->getClientOriginalName(),
            ]);
        }

        $cantidad = count($this->fotos);
        $this->reset('fotos');
        $this->dispatch('notificar', mensaje: $cantidad === 1 ? 'Foto subida' : "{$cantidad} fotos subidas");
    }

    public function eliminarFoto(int $fotoId): void
    {
        $foto = $this->vehiculo->fotos()->findOrFail($fotoId);
        $usuario = auth()->user();

        $esPropia = $foto->user_id === $usuario->id;
        $puede = $usuario->hasRole('admin')
            || ($esPropia && $usuario->can('subir fotos') && ! $this->vehiculo->estaBloqueado());

        abort_unless($puede, 403);

        Storage::disk('public')->delete($foto->ruta);

        app(ServicioAuditoria::class)->registrar($this->vehiculo, 'foto_eliminada', [
            'etapa' => $foto->etapa->value,
            'archivo' => $foto->nombre_original,
        ]);

        $foto->delete();

        $this->dispatch('notificar', mensaje: 'Foto eliminada');
    }

    public function render()
    {
        return view('livewire.vehiculos.gestor-fotos', [
            'fotosPorEtapa' => $this->vehiculo->fotos()
                ->with('usuario')
                ->latest()
                ->get()
                ->groupBy(fn ($foto) => $foto->etapa->value),
            'etapas' => EtapaFoto::cases(),
        ]);
    }
}

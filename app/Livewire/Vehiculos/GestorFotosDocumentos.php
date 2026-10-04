<?php

namespace App\Livewire\Vehiculos;

use App\Livewire\Concerns\ProtegeVinDuplicado;
use App\Models\Vehicle;
use App\Services\ServicioAuditoria;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Fotos, VIN y documentos del vehículo, desde la ficha.
 *
 * Lo usan todos los roles (permiso "completar datos del vehiculo"), cada uno
 * sobre los vehículos que puede ver: el gruero sobre los asignados, el mecánico
 * sobre los del taller, el vendedor sobre los que están en venta.
 *
 * Las fotos y documentos se guardan apenas se suben, sin botón de guardar:
 * en el celular, sacar la foto ya es el gesto completo.
 *
 * En un vehículo vendido o en Junk car (bloqueado) se puede seguir subiendo,
 * porque los papeles suelen llegar después; corregir el VIN, cambiar la
 * portada o borrar queda para el Admin, como el resto del registro bloqueado.
 */
class GestorFotosDocumentos extends Component
{
    use ProtegeVinDuplicado, WithFileUploads;

    public Vehicle $vehiculo;

    public string $vin = '';

    /** Fotos recién elegidas, desde la cámara o la galería. */
    public array $nuevasFotos = [];

    /** Documentos recién elegidos: foto o PDF. */
    public array $nuevosDocumentos = [];

    public function mount(Vehicle $vehiculo): void
    {
        $this->vehiculo = $vehiculo;
        $this->vin = $vehiculo->vin;
    }

    #[On('vehiculo-actualizado')]
    public function refrescar(): void
    {
        $anterior = $this->vehiculo->vin;
        $this->vehiculo->refresh();

        // Si el usuario está escribiendo otro VIN, no se lo pisa.
        if ($this->vin === $anterior) {
            $this->vin = $this->vehiculo->vin;
        }
    }

    /* ------------------------------ Permisos ------------------------------ */

    /** Ver la sección y subir fotos o documentos. */
    public function puedeCompletar(): bool
    {
        $usuario = auth()->user();

        return $usuario->can('completar datos del vehiculo')
            && $this->vehiculo->esVisiblePara($usuario);
    }

    /** Corregir el VIN o cambiar la portada: bloqueado solo deja al Admin. */
    public function puedeModificar(): bool
    {
        return $this->puedeCompletar()
            && (auth()->user()->hasRole('admin') || ! $this->vehiculo->estaBloqueado());
    }

    /** Borrar: el Admin, o quien lo subió mientras el registro no esté bloqueado. */
    public function puedeBorrar(?int $autor): bool
    {
        $usuario = auth()->user();

        if ($usuario->hasRole('admin')) {
            return true;
        }

        return $this->puedeCompletar()
            && $autor === $usuario->id
            && ! $this->vehiculo->estaBloqueado();
    }

    /* ----------------------------- Validación ----------------------------- */

    private function reglasFotos(): array
    {
        return [
            'nuevasFotos' => ['array', 'max:5'],
            'nuevasFotos.*' => ['image', 'max:10240'], // 10 MB
        ];
    }

    private function reglasDocumentos(): array
    {
        return [
            'nuevosDocumentos' => ['array', 'max:5'],
            // Los mismos formatos que el contrato de venta.
            'nuevosDocumentos.*' => ['file', 'mimes:jpg,jpeg,png,webp,heic,pdf', 'max:10240'],
        ];
    }

    protected function messages(): array
    {
        return [
            // Algunos Android guardan en HEIC y el servidor no puede mostrarlo.
            // El iPhone lo convierte a JPG al subir, así que no le pasa.
            'nuevasFotos.*.image' => __('La foto tiene que ser JPG, PNG o WEBP. Si tu cámara guarda en HEIC, cambiala a JPG en sus ajustes.'),
            'nuevosDocumentos.*.mimes' => __('El documento tiene que ser una foto o un PDF.'),
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'nuevasFotos' => __('fotos'),
            'nuevasFotos.*' => __('foto'),
            'nuevosDocumentos' => __('documentos'),
            'nuevosDocumentos.*' => __('documento'),
        ];
    }

    /* -------------------------------- VIN --------------------------------- */

    public function updatedVin(string $valor): void
    {
        $this->vin = strtoupper(trim($valor));
    }

    public function guardarVin(): void
    {
        abort_unless($this->puedeModificar(), 403);

        $this->vin = strtoupper(trim($this->vin));
        $this->validate(['vin' => Vehicle::reglasVin($this->vehiculo->id)]);

        $anterior = $this->vehiculo->vin;

        if ($anterior === $this->vin) {
            return;
        }

        $this->vehiculo->vin = $this->vin;
        $this->sinChocarConElVin(fn () => $this->vehiculo->save());

        app(ServicioAuditoria::class)->registrar($this->vehiculo, 'vehiculo_editado', [
            'cambios' => ['vin' => ['antes' => $anterior, 'despues' => $this->vin]],
        ]);

        $this->dispatch('vehiculo-actualizado');
        $this->dispatch('notificar', mensaje: __('VIN actualizado'));
    }

    /* ------------------------------- Fotos -------------------------------- */

    /** Se guardan apenas terminan de subir: sacar la foto ya es el gesto completo. */
    public function updatedNuevasFotos(): void
    {
        abort_unless($this->puedeCompletar(), 403);

        $this->validate($this->reglasFotos());

        $auditoria = app(ServicioAuditoria::class);

        foreach ($this->nuevasFotos as $foto) {
            $this->vehiculo->fotos()->create([
                'etapa' => 'vehiculo',
                'ruta' => $foto->store("vehiculos/{$this->vehiculo->id}/vehiculo", 'public'),
                'nombre_original' => $foto->getClientOriginalName(),
                'user_id' => auth()->id(),
            ]);

            $auditoria->registrar($this->vehiculo, 'foto_subida', [
                'archivo' => $foto->getClientOriginalName(),
            ]);
        }

        $cantidad = count($this->nuevasFotos);
        $this->reset('nuevasFotos');

        // La portada del encabezado puede haber cambiado (si era la primera foto).
        $this->dispatch('vehiculo-actualizado');
        $this->dispatch('notificar', mensaje: $cantidad === 1
            ? __('Foto subida')
            : __(':n fotos subidas', ['n' => $cantidad]));
    }

    public function marcarPortada(int $id): void
    {
        abort_unless($this->puedeModificar(), 403);

        $foto = $this->vehiculo->fotosVehiculo()->findOrFail($id);
        $this->vehiculo->marcarPortada($foto);

        app(ServicioAuditoria::class)->registrar($this->vehiculo, 'portada_cambiada', [
            'archivo' => $foto->nombre_original,
        ]);

        $this->dispatch('vehiculo-actualizado');
        $this->dispatch('notificar', mensaje: __('Portada actualizada'));
    }

    public function eliminarFoto(int $id): void
    {
        $foto = $this->vehiculo->fotosVehiculo()->findOrFail($id);
        abort_unless($this->puedeBorrar($foto->user_id), 403);

        Storage::disk('public')->delete($foto->ruta);
        $foto->delete();

        app(ServicioAuditoria::class)->registrar($this->vehiculo, 'foto_eliminada', [
            'archivo' => $foto->nombre_original,
        ]);

        // Si era la portada, pasa a serlo la primera que queda.
        $this->dispatch('vehiculo-actualizado');
        $this->dispatch('notificar', mensaje: __('Foto eliminada'));
    }

    /* ----------------------------- Documentos ----------------------------- */

    public function updatedNuevosDocumentos(): void
    {
        abort_unless($this->puedeCompletar(), 403);

        $this->validate($this->reglasDocumentos());

        $auditoria = app(ServicioAuditoria::class);

        foreach ($this->nuevosDocumentos as $archivo) {
            $this->vehiculo->documentos()->create([
                'ruta' => $archivo->store("vehiculos/{$this->vehiculo->id}/documentos", 'public'),
                'nombre_original' => $archivo->getClientOriginalName(),
                'user_id' => auth()->id(),
            ]);

            $auditoria->registrar($this->vehiculo, 'documento_subido', [
                'archivo' => $archivo->getClientOriginalName(),
            ]);
        }

        $cantidad = count($this->nuevosDocumentos);
        $this->reset('nuevosDocumentos');

        $this->dispatch('notificar', mensaje: $cantidad === 1
            ? __('Documento subido')
            : __(':n documentos subidos', ['n' => $cantidad]));
    }

    public function eliminarDocumento(int $id): void
    {
        $documento = $this->vehiculo->documentos()->findOrFail($id);
        abort_unless($this->puedeBorrar($documento->user_id), 403);

        Storage::disk('public')->delete($documento->ruta);
        $documento->delete();

        app(ServicioAuditoria::class)->registrar($this->vehiculo, 'documento_eliminado', [
            'archivo' => $documento->nombre_original,
        ]);

        $this->dispatch('notificar', mensaje: __('Documento eliminado'));
    }

    public function render()
    {
        return view('livewire.vehiculos.gestor-fotos-documentos', [
            'galeria' => $this->vehiculo->fotosVehiculo()->with('usuario')->orderBy('id')->get(),
            'portadaId' => $this->vehiculo->fotoPortada()->first()?->id,
            'archivos' => $this->vehiculo->documentos()->with('usuario')->latest('id')->get(),
        ]);
    }
}

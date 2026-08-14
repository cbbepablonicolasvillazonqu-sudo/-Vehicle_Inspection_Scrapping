<?php

namespace App\Livewire\Vehiculos;

use App\Enums\EstadoTitulo;
use App\Enums\UbicacionDestino;
use App\Models\Vehicle;
use App\Models\VehiclePhoto;
use App\Services\ServicioAuditoria;
use App\Services\ServicioEstadoVehiculo;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Alta y edición de vehículos (solo Admin).
 */
#[Layout('layouts.app')]
class FormularioVehiculo extends Component
{
    use AuthorizesRequests, WithFileUploads;

    public ?Vehicle $vehiculo = null;

    public string $marca = '';

    public string $modelo = '';

    public string $anio = '';

    public string $vin = '';

    public string $millas = '';

    public string $precio_compra = '';

    public string $fecha_compra = '';

    public string $ubicacion_destino = '';

    public string $estado_titulo = '';

    public string $notas = '';

    /** Foto principal del vehículo (la misma en alta y edición). */
    public $foto = null;

    public function mount(?Vehicle $vehiculo = null): void
    {
        if ($vehiculo && $vehiculo->exists) {
            $this->authorize('update', $vehiculo);

            $this->vehiculo = $vehiculo;
            $this->marca = $vehiculo->marca;
            $this->modelo = $vehiculo->modelo;
            $this->anio = (string) $vehiculo->anio;
            $this->vin = $vehiculo->vin;
            $this->millas = (string) ($vehiculo->millas ?? '');
            $this->precio_compra = (string) ($vehiculo->precio_compra ?? '');
            $this->fecha_compra = $vehiculo->fecha_compra?->format('Y-m-d') ?? now()->format('Y-m-d');
            $this->ubicacion_destino = $vehiculo->ubicacion_destino?->value ?? '';
            $this->estado_titulo = $vehiculo->estado_titulo?->value ?? '';
            $this->notas = (string) $vehiculo->notas;
        } else {
            $this->authorize('create', Vehicle::class);
            $this->fecha_compra = now()->format('Y-m-d');
        }
    }

    protected function rules(): array
    {
        return [
            'marca' => ['required', 'string', 'max:60'],
            'modelo' => ['required', 'string', 'max:60'],
            'anio' => ['required', 'integer', 'between:1950,'.(now()->year + 1)],
            'vin' => [
                'required', 'string', 'size:17', 'regex:/^[A-HJ-NPR-Z0-9]{17}$/',
                Rule::unique('vehicles', 'vin')->ignore($this->vehiculo?->id)->withoutTrashed(),
            ],
            'millas' => ['required', 'integer', 'min:0', 'max:2000000'],
            'precio_compra' => ['required', 'numeric', 'min:0', 'max:9999999'],
            'fecha_compra' => ['required', 'date', 'before_or_equal:today'],
            'ubicacion_destino' => ['required', Rule::enum(UbicacionDestino::class)],
            'estado_titulo' => ['required', Rule::enum(EstadoTitulo::class)],
            'notas' => ['nullable', 'string', 'max:5000'],
            'foto' => ['nullable', 'image', 'max:10240'], // 10 MB
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'ubicacion_destino' => __('dónde está'),
            'foto' => __('foto del vehículo'),
        ];
    }

    /** Foto principal ya guardada (para mostrarla al editar). */
    public function fotoActual(): ?VehiclePhoto
    {
        return $this->vehiculo?->fotos()->whereNull('expense_id')->latest('id')->first();
    }

    /**
     * Valida la foto apenas se elige, sin esperar al Guardar: si el archivo no
     * es una imagen el usuario lo sabe en el momento, no tras pulsar el botón.
     */
    public function updatedFoto(): void
    {
        $this->validateOnly('foto');
    }

    public function quitarFotoSeleccionada(): void
    {
        $this->reset('foto');
        $this->resetValidation('foto');
    }

    public function updatedVin(string $valor): void
    {
        $this->vin = strtoupper(trim($valor));
    }

    public function guardar(): mixed
    {
        $this->vin = strtoupper(trim($this->vin));
        $datos = $this->validate();
        $datos['notas'] = filled($datos['notas'] ?? null) ? $datos['notas'] : null;

        $foto = $datos['foto'] ?? null;
        unset($datos['foto']);

        $auditoria = app(ServicioAuditoria::class);

        if ($this->vehiculo) {
            $this->authorize('update', $this->vehiculo);

            // Diff de cambios para la auditoría (antes de guardar).
            $this->vehiculo->fill($datos);
            $cambios = [];

            foreach (array_keys($this->vehiculo->getDirty()) as $campo) {
                $cambios[$campo] = [
                    'antes' => $this->normalizar($this->vehiculo->getOriginal($campo)),
                    'despues' => $this->normalizar($this->vehiculo->{$campo}),
                ];
            }

            $this->vehiculo->save();

            if ($cambios !== []) {
                $auditoria->registrar($this->vehiculo, 'vehiculo_editado', ['cambios' => $cambios]);
            }

            if ($foto) {
                $this->guardarFoto($this->vehiculo, $foto, $auditoria);
            }

            return $this->redirectRoute('vehiculos.ficha', $this->vehiculo, navigate: false);
        }

        $vehiculo = Vehicle::create($datos + [
            'created_by' => auth()->id(),
            'estado' => \App\Enums\EstadoVehiculo::Comprado,
        ]);

        app(ServicioEstadoVehiculo::class)->registrarEstadoInicial(auth()->user(), $vehiculo);
        $auditoria->registrar($vehiculo, 'vehiculo_creado', ['vin' => $vehiculo->vin]);

        if ($foto) {
            $this->guardarFoto($vehiculo, $foto, $auditoria);
        }

        session()->flash('ok', 'Vehículo registrado correctamente.');

        return $this->redirectRoute('vehiculos.ficha', $vehiculo, navigate: false);
    }

    /**
     * Guarda la foto principal del vehículo. Es una sola: si ya había otra,
     * se reemplaza (así la de alta y la de edición son siempre la misma).
     */
    private function guardarFoto(Vehicle $vehiculo, $foto, ServicioAuditoria $auditoria): void
    {
        $anteriores = $vehiculo->fotos()->whereNull('expense_id')->get();

        $ruta = $foto->store("vehiculos/{$vehiculo->id}/vehiculo", 'public');

        $vehiculo->fotos()->create([
            'etapa' => 'vehiculo',
            'ruta' => $ruta,
            'nombre_original' => $foto->getClientOriginalName(),
            'user_id' => auth()->id(),
        ]);

        foreach ($anteriores as $vieja) {
            Storage::disk('public')->delete($vieja->ruta);
            $vieja->delete();
        }

        $auditoria->registrar($vehiculo, 'foto_vehiculo_actualizada', [
            'archivo' => $foto->getClientOriginalName(),
        ]);
    }

    private function normalizar(mixed $valor): mixed
    {
        if ($valor instanceof \BackedEnum) {
            return $valor->value;
        }

        if ($valor instanceof \DateTimeInterface) {
            return $valor->format('Y-m-d');
        }

        return $valor;
    }

    public function render()
    {
        return view('livewire.vehiculos.formulario-vehiculo', [
            'ubicaciones' => UbicacionDestino::opciones(),
            'titulos' => EstadoTitulo::opciones(),
            'fotoActual' => $this->fotoActual(),
        ])->title($this->vehiculo ? 'Editar vehículo' : 'Nuevo vehículo');
    }
}

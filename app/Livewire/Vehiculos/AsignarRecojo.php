<?php

namespace App\Livewire\Vehiculos;

use App\Enums\EstadoVehiculo;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\ServicioAuditoria;
use App\Services\ServicioEstadoVehiculo;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Asignación de recojo: el Admin carga los datos mínimos del vehículo y la
 * ubicación (enlace de Google Maps), y se lo asigna a un Gruero.
 *
 * El Gruero solo verá los vehículos asignados a él.
 */
#[Layout('layouts.app')]
#[Title('Asignar recojo')]
class AsignarRecojo extends Component
{
    public string $marca = '';

    public string $modelo = '';

    public string $anio = '';

    public string $vin = '';

    public string $ubicacion_origen_url = '';

    public string $asignado_a = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->can('asignar recojo'), 403);
    }

    protected function rules(): array
    {
        return [
            'marca' => ['required', 'string', 'max:60'],
            'modelo' => ['required', 'string', 'max:60'],
            'anio' => ['required', 'integer', 'between:1950,'.(now()->year + 1)],
            'vin' => [
                'required', 'string', 'size:17', 'regex:/^[A-HJ-NPR-Z0-9]{17}$/',
                Rule::unique('vehicles', 'vin')->withoutTrashed(),
            ],
            // Solo enlaces de Google Maps, para evitar URLs arbitrarias.
            'ubicacion_origen_url' => [
                'required', 'url', 'max:2048',
                'regex:/^https:\/\/([a-z0-9-]+\.)*(google\.[a-z.]+\/maps|maps\.app\.goo\.gl|goo\.gl\/maps)/i',
            ],
            'asignado_a' => [
                'required',
                Rule::exists('users', 'id')->where(
                    fn ($q) => $q->whereIn('id', User::role('gruero')->pluck('id')),
                ),
            ],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'ubicacion_origen_url' => __('ubicación (enlace de Google Maps)'),
            'asignado_a' => __('gruero'),
        ];
    }

    public function updatedVin(string $valor): void
    {
        $this->vin = strtoupper(trim($valor));
    }

    public function guardar(): mixed
    {
        abort_unless(auth()->user()->can('asignar recojo'), 403);

        $this->vin = strtoupper(trim($this->vin));
        $datos = $this->validate();

        $vehiculo = Vehicle::create($datos + [
            'estado' => EstadoVehiculo::Comprado,
            'created_by' => auth()->id(),
        ]);

        app(ServicioEstadoVehiculo::class)->registrarEstadoInicial(auth()->user(), $vehiculo);

        app(ServicioAuditoria::class)->registrar($vehiculo, 'recojo_asignado', [
            'gruero' => User::find($datos['asignado_a'])?->name,
            'ubicacion' => $datos['ubicacion_origen_url'],
        ]);

        session()->flash('ok', __('Recojo asignado correctamente.'));

        return $this->redirectRoute('vehiculos.ficha', $vehiculo, navigate: false);
    }

    public function render()
    {
        return view('livewire.vehiculos.asignar-recojo', [
            'grueros' => User::role('gruero')->orderBy('name')->get(['id', 'name']),
        ]);
    }
}

<?php

namespace App\Livewire\Vehiculos;

use App\Enums\EstadoVehiculo;
use App\Enums\MetodoPago;
use App\Models\Vehicle;
use App\Services\ServicioAuditoria;
use App\Services\ServicioEstadoVehiculo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Registro de la venta (Vendedor/Admin sobre vehículos listos o publicados).
 * Al vender: estado → Vendido y el registro queda bloqueado para todos
 * excepto Admin, que puede editar la venta cerrada o eliminarla (revierte
 * el estado al anterior según el historial).
 */
class GestorVenta extends Component
{
    use WithFileUploads;

    public Vehicle $vehiculo;

    public bool $editando = false;

    /*
     * Ningún dato de la venta es obligatorio: a veces se cierra el trato y la
     * información llega después. Las propiedades son opcionales porque un campo
     * vacío tiene que viajar como null, no como '' (ver normalizarVacios()).
     */
    public ?string $fecha_venta = null;

    public ?string $precio_venta = null;

    public ?string $nombre_comprador = null;

    public ?string $telefono_comprador = null;

    public ?string $email_comprador = null;

    public ?string $metodo_pago = null;

    /** Contrato firmado: foto o PDF. */
    public $contrato = null;

    public ?string $notas = null;

    public function mount(Vehicle $vehiculo): void
    {
        $this->vehiculo = $vehiculo;
        $this->fecha_venta = now()->format('Y-m-d');
    }

    #[On('vehiculo-actualizado')]
    public function refrescar(): void
    {
        $this->vehiculo->refresh();
    }

    protected function rules(): array
    {
        return [
            // Ningún campo es obligatorio. Lo que sí se escriba tiene que ser
            // válido: un correo mal escrito sigue siendo un error.
            'fecha_venta' => ['nullable', 'date', 'before_or_equal:today'],
            'precio_venta' => ['nullable', 'numeric', 'min:0.01', 'max:9999999'],
            'nombre_comprador' => ['nullable', 'string', 'max:120'],
            'telefono_comprador' => ['nullable', 'string', 'max:30'],
            'email_comprador' => ['nullable', 'email', 'max:150'],
            'metodo_pago' => ['nullable', Rule::enum(MetodoPago::class)],
            'notas' => ['nullable', 'string', 'max:5000'],
            // Contrato firmado: imagen o PDF, hasta 10 MB.
            'contrato' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,heic,pdf', 'max:10240'],
        ];
    }

    /**
     * Convierte los campos vacíos en null ANTES de validar.
     *
     * Hace falta porque 'nullable' solo saltea la validación cuando el valor es
     * null: una cadena vacía sigue considerándose presente y choca contra
     * 'date', 'numeric', 'email' y el enum. El middleware que hace esto en las
     * peticiones normales no alcanza a las propiedades de Livewire.
     */
    private function normalizarVacios(): void
    {
        foreach (['fecha_venta', 'precio_venta', 'nombre_comprador', 'telefono_comprador',
            'email_comprador', 'metodo_pago', 'notas'] as $campo) {
            if (blank($this->$campo)) {
                $this->$campo = null;
            }
        }
    }

    /** ¿El formulario quedó entero en blanco? */
    private function estaVacio(): bool
    {
        return blank($this->fecha_venta) && blank($this->precio_venta)
            && blank($this->nombre_comprador) && blank($this->telefono_comprador)
            && blank($this->email_comprador) && blank($this->metodo_pago)
            && blank($this->notas) && blank($this->contrato);
    }

    protected function validationAttributes(): array
    {
        return [
            'email_comprador' => __('correo electrónico del comprador'),
            'contrato' => __('contrato'),
        ];
    }

    /** Valida el contrato apenas se elige, igual que la foto del vehículo. */
    public function updatedContrato(): void
    {
        $this->validateOnly('contrato');
    }

    public function quitarContratoSeleccionado(): void
    {
        $this->reset('contrato');
        $this->resetValidation('contrato');
    }

    /** Guarda el contrato en disco y devuelve ruta y nombre original. */
    private function guardarContrato($archivo): array
    {
        return [
            'contrato_ruta' => $archivo->store("ventas/{$this->vehiculo->id}", 'public'),
            'contrato_nombre' => $archivo->getClientOriginalName(),
        ];
    }

    /** ¿Puede registrar una venta nueva? */
    public function puedeVender(): bool
    {
        return $this->vehiculo->admiteRegistrarVenta(auth()->user());
    }

    /** ¿Puede editar/eliminar la venta cerrada? Solo Admin. */
    public function puedeEditarCerrada(): bool
    {
        return auth()->user()->can('editar ventas cerradas');
    }

    public function registrar(): void
    {
        abort_unless($this->puedeVender(), 403);

        $this->normalizarVacios();

        // Ningún campo es obligatorio, pero una venta sin un solo dato sería un
        // registro fantasma creado por un clic de más.
        if ($this->estaVacio()) {
            $this->dispatch('notificar', mensaje: __('Completá al menos un dato para registrar la venta'));

            return;
        }

        $datos = $this->validate();

        $archivo = $datos['contrato'] ?? null;
        unset($datos['contrato']);

        if ($archivo) {
            $datos += $this->guardarContrato($archivo);
        }

        DB::transaction(function () use ($datos) {
            $this->vehiculo->venta()->create($datos + ['user_id' => auth()->id()]);

            app(ServicioEstadoVehiculo::class)->cambiar(
                auth()->user(),
                $this->vehiculo,
                EstadoVehiculo::Vendido,
                'Venta registrada',
                interno: true,
            );

            app(ServicioAuditoria::class)->registrar($this->vehiculo, 'venta_registrada', [
                'precio' => $datos['precio_venta'],
                'comprador' => $datos['nombre_comprador'],
            ]);
        });

        $this->dispatch('vehiculo-actualizado');
        $this->dispatch('notificar', mensaje: __('Venta registrada').' 🎉');
    }

    public function editar(): void
    {
        abort_unless($this->puedeEditarCerrada() && $this->vehiculo->venta, 403);

        $venta = $this->vehiculo->venta;

        // Todo con navegación segura: este es justamente el formulario con el
        // que el Admin completa una venta que se guardó a medias.
        $this->resetValidation();
        $this->fecha_venta = $venta->fecha_venta?->format('Y-m-d');
        $this->precio_venta = $venta->precio_venta === null ? null : (string) $venta->precio_venta;
        $this->nombre_comprador = $venta->nombre_comprador;
        $this->telefono_comprador = $venta->telefono_comprador;
        $this->email_comprador = $venta->email_comprador;
        $this->metodo_pago = $venta->metodo_pago?->value;
        $this->reset('contrato');
        $this->notas = $venta->notas;
        $this->editando = true;
    }

    public function actualizar(): void
    {
        abort_unless($this->puedeEditarCerrada() && $this->vehiculo->venta, 403);

        $this->normalizarVacios();
        $datos = $this->validate();

        $archivo = $datos['contrato'] ?? null;
        unset($datos['contrato']);

        $venta = $this->vehiculo->venta;
        $anterior = ['precio' => $venta->precio_venta, 'comprador' => $venta->nombre_comprador];

        if ($archivo) {
            $rutaVieja = $venta->contrato_ruta;
            $datos += $this->guardarContrato($archivo);

            if ($rutaVieja) {
                Storage::disk('public')->delete($rutaVieja);
            }
        }

        $venta->update($datos);

        app(ServicioAuditoria::class)->registrar($this->vehiculo, 'venta_editada', [
            'antes' => $anterior,
            'despues' => ['precio' => $datos['precio_venta'], 'comprador' => $datos['nombre_comprador']],
        ]);

        $this->editando = false;
        $this->dispatch('vehiculo-actualizado');
        $this->dispatch('notificar', mensaje: __('Venta actualizada'));
    }

    public function cancelarEdicion(): void
    {
        $this->editando = false;
    }

    /** Elimina la venta y revierte el estado al que tenía antes de venderse. */
    public function eliminarVenta(): void
    {
        abort_unless($this->puedeEditarCerrada() && $this->vehiculo->venta, 403);

        DB::transaction(function () {
            $venta = $this->vehiculo->venta;

            // Estado previo a "vendido" según el historial (respaldo: Publicado).
            $ultimoCambio = $this->vehiculo->historialEstados()
                ->where('estado_nuevo', EstadoVehiculo::Vendido->value)
                ->latest('id')
                ->first();

            $estadoPrevio = $ultimoCambio?->estado_anterior ?? EstadoVehiculo::Publicado;

            app(ServicioAuditoria::class)->registrar($this->vehiculo, 'venta_eliminada', [
                'precio' => $venta->precio_venta,
                'comprador' => $venta->nombre_comprador,
            ]);

            if ($venta->contrato_ruta) {
                Storage::disk('public')->delete($venta->contrato_ruta);
            }

            $venta->delete();

            app(ServicioEstadoVehiculo::class)->cambiar(
                auth()->user(),
                $this->vehiculo,
                $estadoPrevio,
                'Venta eliminada por Admin',
                interno: true,
            );
        });

        $this->dispatch('vehiculo-actualizado');
        $this->dispatch('notificar', mensaje: __('Venta eliminada; estado revertido'));
    }

    public function render()
    {
        return view('livewire.vehiculos.gestor-venta', [
            'venta' => $this->vehiculo->venta?->load('usuario'),
            'metodos' => MetodoPago::opciones(),
        ]);
    }
}

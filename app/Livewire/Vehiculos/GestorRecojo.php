<?php

namespace App\Livewire\Vehiculos;

use App\Enums\EstadoTitulo;
use App\Enums\MetodoPagoGruero;
use App\Enums\UbicacionDestino;
use App\Models\Vehicle;
use App\Services\ServicioAuditoria;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Datos que registra el Gruero al recoger el vehículo: forma de pago,
 * dónde lo dejó, la titulación y cuánto pagó por él.
 * (El catalizador se registra en el formulario de Junk car.)
 */
class GestorRecojo extends Component
{
    public Vehicle $vehiculo;

    public string $metodo_pago_gruero = '';

    public string $ubicacion_destino = '';

    public string $estado_titulo = '';

    public string $monto_pagado = '';

    public function mount(Vehicle $vehiculo): void
    {
        $this->vehiculo = $vehiculo;
        $this->sincronizar();
    }

    private function sincronizar(): void
    {
        $this->metodo_pago_gruero = $this->vehiculo->metodo_pago_gruero?->value ?? '';
        $this->ubicacion_destino = $this->vehiculo->ubicacion_destino?->value ?? '';
        $this->estado_titulo = $this->vehiculo->estado_titulo?->value ?? '';
        $this->monto_pagado = $this->vehiculo->monto_pagado !== null
            ? (string) $this->vehiculo->monto_pagado
            : '';
    }

    #[On('vehiculo-actualizado')]
    public function refrescar(): void
    {
        $this->vehiculo->refresh();
        $this->sincronizar();
    }

    /** Solo el Gruero asignado (o el Admin) puede registrar el recojo. */
    public function puedeRegistrar(): bool
    {
        $usuario = auth()->user();

        if ($usuario->hasRole('admin')) {
            return true;
        }

        return $usuario->can('registrar recojo')
            && $this->vehiculo->asignado_a === $usuario->id
            && ! $this->vehiculo->estaBloqueado();
    }

    protected function rules(): array
    {
        return [
            'metodo_pago_gruero' => ['required', Rule::enum(MetodoPagoGruero::class)],
            'ubicacion_destino' => ['required', Rule::enum(UbicacionDestino::class)],
            'estado_titulo' => ['required', Rule::enum(EstadoTitulo::class)],
            'monto_pagado' => ['required', 'numeric', 'min:0', 'max:9999999'],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'metodo_pago_gruero' => __('forma de pago'),
            'ubicacion_destino' => __('ubicación de destino'),
            'estado_titulo' => __('titulación'),
            'monto_pagado' => __('monto pagado'),
        ];
    }

    public function guardar(): void
    {
        abort_unless($this->puedeRegistrar(), 403);

        $datos = $this->validate();

        $monto = number_format(round((float) $datos['monto_pagado'], 2), 2, '.', '');

        // Lo que paga el gruero ES el precio de compra del vehículo, y la
        // fecha de compra es el día del recojo. La fecha se fija la primera
        // vez: si después corrige el monto, no se mueve el día del recojo.
        $fechaCompra = $this->vehiculo->fecha_compra?->format('Y-m-d') ?? now()->toDateString();

        $this->vehiculo->forceFill([
            'metodo_pago_gruero' => $datos['metodo_pago_gruero'],
            'ubicacion_destino' => $datos['ubicacion_destino'],
            'estado_titulo' => $datos['estado_titulo'],
            'monto_pagado' => $monto,
            'precio_compra' => $monto,
            'fecha_compra' => $fechaCompra,
        ])->save();

        app(ServicioAuditoria::class)->registrar($this->vehiculo, 'recojo_registrado', [
            'pago' => $datos['metodo_pago_gruero'],
            'destino' => $datos['ubicacion_destino'],
            'titulacion' => $datos['estado_titulo'],
            'monto' => $monto,
            'precio_compra' => $monto,
            'fecha_compra' => $fechaCompra,
        ]);

        $this->dispatch('vehiculo-actualizado');
        $this->dispatch('notificar', mensaje: __('Recojo registrado'));
    }

    public function render()
    {
        return view('livewire.vehiculos.gestor-recojo', [
            'metodos' => MetodoPagoGruero::opciones(),
            'destinos' => UbicacionDestino::opciones(),
            'titulos' => EstadoTitulo::opciones(),
        ]);
    }
}

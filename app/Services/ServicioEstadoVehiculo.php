<?php

namespace App\Services;

use App\Enums\EstadoVehiculo;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Reglas de negocio de las transiciones de estado por rol.
 *
 * - Admin: puede mover a cualquier estado (Vendido y Desguace solo mediante
 *   sus formularios, que capturan los datos de la operación).
 * - Mecánico: Comprado → En reparación (él inicia la revisión/ingreso al
 *   taller) y En reparación ⇄ Listo para la venta.
 * - Vendedor: Listo ⇄ Publicado (Vendido se marca al registrar la venta).
 * - Vendido/Desguace: se revierten únicamente eliminando la venta o el
 *   desguace (acción exclusiva del Admin), nunca con un cambio directo.
 *
 * Todo cambio queda en vehicle_status_histories y en audit_logs.
 */
class ServicioEstadoVehiculo
{
    /** @return list<EstadoVehiculo> */
    public function transicionesPermitidas(User $usuario, Vehicle $vehiculo): array
    {
        if (! $usuario->can('cambiar estado')) {
            return [];
        }

        $estado = $vehiculo->estado;

        // Vendido y Desguace se revierten eliminando la venta / el desguace.
        if ($estado->esFinal()) {
            return [];
        }

        if ($usuario->hasRole('admin')) {
            return array_values(array_filter(
                EstadoVehiculo::cases(),
                fn (EstadoVehiculo $destino) => $destino !== $estado && ! $destino->esFinal(),
            ));
        }

        $matriz = [
            'mecanico' => [
                EstadoVehiculo::Comprado->value => [EstadoVehiculo::EnReparacion],
                EstadoVehiculo::EnReparacion->value => [EstadoVehiculo::Listo],
                EstadoVehiculo::Listo->value => [EstadoVehiculo::EnReparacion],
            ],
            'vendedor' => [
                EstadoVehiculo::Listo->value => [EstadoVehiculo::Publicado],
                EstadoVehiculo::Publicado->value => [EstadoVehiculo::Listo],
            ],
        ];

        $permitidas = [];

        foreach ($matriz as $rol => $porEstado) {
            if ($usuario->hasRole($rol)) {
                $permitidas = array_merge($permitidas, $porEstado[$estado->value] ?? []);
            }
        }

        return array_values(array_unique($permitidas, SORT_REGULAR));
    }

    /**
     * Cambia el estado registrando historial y auditoría.
     *
     * @param  bool  $interno  true cuando lo invoca otro servicio que ya
     *                         validó sus propias reglas (venta, desguace).
     */
    public function cambiar(
        User $usuario,
        Vehicle $vehiculo,
        EstadoVehiculo $nuevo,
        ?string $nota = null,
        bool $interno = false,
    ): void {
        if (! $interno && ! in_array($nuevo, $this->transicionesPermitidas($usuario, $vehiculo), true)) {
            throw ValidationException::withMessages([
                'estado' => 'No tienes permiso para pasar este vehículo a "'.$nuevo->etiqueta().'".',
            ]);
        }

        DB::transaction(function () use ($usuario, $vehiculo, $nuevo, $nota) {
            $anterior = $vehiculo->estado;

            $vehiculo->forceFill(['estado' => $nuevo])->save();

            $vehiculo->historialEstados()->create([
                'estado_anterior' => $anterior?->value,
                'estado_nuevo' => $nuevo->value,
                'user_id' => $usuario->id,
                'nota' => $nota,
            ]);

            app(ServicioAuditoria::class)->registrar($vehiculo, 'cambio_estado', [
                'de' => $anterior?->etiqueta(),
                'a' => $nuevo->etiqueta(),
                'nota' => $nota,
            ], $usuario);
        });
    }

    /** Historial inicial al registrar un vehículo (→ Comprado). */
    public function registrarEstadoInicial(User $usuario, Vehicle $vehiculo): void
    {
        $vehiculo->historialEstados()->create([
            'estado_anterior' => null,
            'estado_nuevo' => $vehiculo->estado->value,
            'user_id' => $usuario->id,
            'nota' => 'Registro inicial',
        ]);
    }
}

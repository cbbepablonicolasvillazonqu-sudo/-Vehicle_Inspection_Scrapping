<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    protected $fillable = [
        'vehicle_id',
        'user_id',
        'accion',
        'detalles',
    ];

    protected function casts(): array
    {
        return [
            'detalles' => 'array',
        ];
    }

    public function vehiculo(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function etiquetaAccion(): string
    {
        return __(match ($this->accion) {
            'vehiculo_creado' => 'Vehículo registrado',
            'vehiculo_editado' => 'Datos editados',
            'vehiculo_eliminado' => 'Vehículo eliminado',
            'cambio_estado' => 'Cambio de estado',
            'gasto_registrado' => 'Gasto registrado',
            'gasto_editado' => 'Gasto editado',
            'gasto_eliminado' => 'Gasto eliminado',
            'foto_subida' => 'Foto subida',
            'foto_eliminada' => 'Foto eliminada',
            'venta_registrada' => 'Venta registrada',
            'venta_editada' => 'Venta editada',
            'venta_eliminada' => 'Venta eliminada',
            'desguace_registrado' => 'Junk car registrado',
            'desguace_editado' => 'Junk car editado',
            'desguace_eliminado' => 'Junk car eliminado',
            'precio_sugerido_actualizado' => 'Precio sugerido actualizado',
            // Flujo de grúa y Junk car.
            'recojo_asignado' => 'Recojo asignado',
            'recojo_registrado' => 'Recojo registrado',
            'enviado_a_junk' => 'Enviado a Junk car',
            'junk_car_completado' => 'Junk car completado',
            'foto_vehiculo_actualizada' => 'Foto del vehículo actualizada',
            default => ucfirst(str_replace('_', ' ', $this->accion)),
        });
    }
}

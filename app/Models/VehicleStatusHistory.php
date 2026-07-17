<?php

namespace App\Models;

use App\Enums\EstadoVehiculo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleStatusHistory extends Model
{
    protected $fillable = [
        'vehicle_id',
        'estado_anterior',
        'estado_nuevo',
        'user_id',
        'nota',
    ];

    protected function casts(): array
    {
        return [
            'estado_anterior' => EstadoVehiculo::class,
            'estado_nuevo' => EstadoVehiculo::class,
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
}

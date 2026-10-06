<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Documento del vehículo (título, registro, etc.), como foto o PDF.
 */
class VehicleDocument extends Model
{
    protected $fillable = [
        'vehicle_id',
        'ruta',
        'nombre_original',
        'user_id',
    ];

    public function vehiculo(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** URL con sesión y permiso; relativa por el mismo motivo que VehiclePhoto::url(). */
    public function url(): string
    {
        return route('archivos.documento', $this, absolute: false);
    }

    public function esPdf(): bool
    {
        return str_ends_with(strtolower($this->ruta), '.pdf');
    }
}

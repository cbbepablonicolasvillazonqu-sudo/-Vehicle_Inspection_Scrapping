<?php

namespace App\Models;

use App\Enums\MetodoPago;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sale extends Model
{
    protected $fillable = [
        'vehicle_id',
        'fecha_venta',
        'precio_venta',
        'nombre_comprador',
        'telefono_comprador',
        'metodo_pago',
        'notas',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'fecha_venta' => 'date',
            'precio_venta' => 'decimal:2',
            'metodo_pago' => MetodoPago::class,
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

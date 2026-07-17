<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScrapRecord extends Model
{
    protected $fillable = [
        'vehicle_id',
        'fecha',
        'monto_recibido',
        'empresa',
        'notas',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'monto_recibido' => 'decimal:2',
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

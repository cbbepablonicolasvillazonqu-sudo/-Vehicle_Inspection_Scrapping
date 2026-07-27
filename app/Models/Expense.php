<?php

namespace App\Models;

use App\Enums\CategoriaGasto;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Expense extends Model
{
    protected $fillable = [
        'vehicle_id',
        'categoria',
        'descripcion',
        'monto',
        'fecha',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'categoria' => CategoriaGasto::class,
            'monto' => 'decimal:2',
            'fecha' => 'date',
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

    /** Fotos adjuntas al gasto (único módulo con carga de fotos). */
    public function fotos(): HasMany
    {
        return $this->hasMany(VehiclePhoto::class, 'expense_id');
    }
}

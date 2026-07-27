<?php

namespace App\Models;

use App\Enums\EtapaFoto;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehiclePhoto extends Model
{
    protected $fillable = [
        'vehicle_id',
        'expense_id',
        'etapa',
        'ruta',
        'nombre_original',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'etapa' => EtapaFoto::class,
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

    /** Gasto al que pertenece la foto. */
    public function gasto(): BelongsTo
    {
        return $this->belongsTo(Expense::class, 'expense_id');
    }

    /**
     * URL pública de la imagen (requiere `php artisan storage:link`).
     *
     * Usa asset() —no Storage::url()— para que la URL apunte al host real de
     * la petición (localhost, túnel de Cloudflare o dominio en producción) y no
     * al APP_URL fijo. Así una persona externa que entra por el túnel también
     * ve las fotos. Con trustProxies=* la petición conserva host/esquema reales.
     */
    public function url(): string
    {
        return asset('storage/'.ltrim($this->ruta, '/'));
    }
}

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
        'es_portada',
        'ruta',
        'nombre_original',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'etapa' => EtapaFoto::class,
            'es_portada' => 'boolean',
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
     * URL de la imagen, que solo responde con sesión y permiso
     * (ArchivoController). Antes era /storage/..., abierta a cualquiera.
     *
     * Relativa a propósito: así sirve igual en localhost, en el túnel de
     * Cloudflare y en el dominio de producción, sin depender del APP_URL.
     */
    public function url(): string
    {
        return route('archivos.foto', $this, absolute: false);
    }
}

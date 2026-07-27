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
        'email_comprador',
        'metodo_pago',
        'contrato_ruta',
        'contrato_nombre',
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

    /** URL pública del contrato (imagen o PDF), si se cargó uno. */
    public function contratoUrl(): ?string
    {
        return $this->contrato_ruta ? asset('storage/'.ltrim($this->contrato_ruta, '/')) : null;
    }

    /** ¿El contrato es un PDF? (para mostrar icono en vez de miniatura). */
    public function contratoEsPdf(): bool
    {
        return str_ends_with(strtolower((string) $this->contrato_ruta), '.pdf');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

<?php

namespace App\Models;

use App\Enums\EstadoTitulo;
use App\Enums\EstadoVehiculo;
use App\Enums\LugarCompra;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vehicle extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'marca',
        'modelo',
        'anio',
        'vin',
        'millas',
        'precio_compra',
        'fecha_compra',
        'lugar_compra',
        'estado_titulo',
        'estado',
        'notas',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'anio' => 'integer',
            'millas' => 'integer',
            'precio_compra' => 'decimal:2',
            'fecha_compra' => 'date',
            'lugar_compra' => LugarCompra::class,
            'estado_titulo' => EstadoTitulo::class,
            'estado' => EstadoVehiculo::class,
        ];
    }

    /* ----------------------------- Relaciones ----------------------------- */

    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function fotos(): HasMany
    {
        return $this->hasMany(VehiclePhoto::class);
    }

    public function historialEstados(): HasMany
    {
        return $this->hasMany(VehicleStatusHistory::class);
    }

    public function auditoria(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    /* ------------------------------- Scopes ------------------------------- */

    /**
     * Restringe la consulta a los vehículos que el rol del usuario puede ver:
     * mecánico → solo en reparación; vendedor → listos/publicados/vendidos;
     * admin y comprador → todos.
     */
    public function scopeVisiblePara(Builder $query, User $usuario): Builder
    {
        if ($usuario->hasRole('mecanico')) {
            return $query->where('estado', EstadoVehiculo::EnReparacion);
        }

        if ($usuario->hasRole('vendedor')) {
            return $query->whereIn('estado', [
                EstadoVehiculo::Listo,
                EstadoVehiculo::Publicado,
                EstadoVehiculo::Vendido,
            ]);
        }

        return $query;
    }

    /** Búsqueda por marca, modelo o VIN. */
    public function scopeBuscar(Builder $query, string $termino): Builder
    {
        $termino = trim($termino);

        if ($termino === '') {
            return $query;
        }

        return $query->where(function (Builder $q) use ($termino) {
            $q->where('marca', 'like', "%{$termino}%")
                ->orWhere('modelo', 'like', "%{$termino}%")
                ->orWhere('vin', 'like', "%{$termino}%");
        });
    }

    /* ------------------------------ Ayudantes ------------------------------ */

    public function nombreCompleto(): string
    {
        return "{$this->anio} {$this->marca} {$this->modelo}";
    }

    /** Vendido o desguazado: bloqueado para todos excepto Admin. */
    public function estaBloqueado(): bool
    {
        return $this->estado->esFinal();
    }

    /** Versión por-modelo del scope visiblePara (para policies). */
    public function esVisiblePara(User $usuario): bool
    {
        if ($usuario->hasRole('mecanico')) {
            return $this->estado === EstadoVehiculo::EnReparacion;
        }

        if ($usuario->hasRole('vendedor')) {
            return in_array($this->estado, [
                EstadoVehiculo::Listo,
                EstadoVehiculo::Publicado,
                EstadoVehiculo::Vendido,
            ], true);
        }

        return true;
    }
}

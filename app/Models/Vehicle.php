<?php

namespace App\Models;

use App\Enums\EstadoTitulo;
use App\Enums\EstadoVehiculo;
use App\Enums\LugarCompra;
use App\Enums\MetodoPagoGruero;
use App\Enums\UbicacionDestino;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
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
        'precio_sugerido',
        'fecha_compra',
        'lugar_compra',
        'estado_titulo',
        'estado',
        'notas',
        'created_by',
        'ubicacion_origen_url',
        'ubicacion_destino',
        'asignado_a',
        'metodo_pago_gruero',
        'tiene_catalizador',
        'monto_pagado',
    ];

    protected function casts(): array
    {
        return [
            'anio' => 'integer',
            'millas' => 'integer',
            'precio_compra' => 'decimal:2',
            'precio_sugerido' => 'decimal:2',
            'monto_pagado' => 'decimal:2',
            'fecha_compra' => 'date',
            'lugar_compra' => LugarCompra::class,
            'estado_titulo' => EstadoTitulo::class,
            'estado' => EstadoVehiculo::class,
            'ubicacion_destino' => UbicacionDestino::class,
            'metodo_pago_gruero' => MetodoPagoGruero::class,
            'tiene_catalizador' => 'boolean',
        ];
    }

    /* ----------------------------- Relaciones ----------------------------- */

    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Gruero al que el Admin asignó el recojo. */
    public function gruero(): BelongsTo
    {
        return $this->belongsTo(User::class, 'asignado_a');
    }

    public function fotos(): HasMany
    {
        return $this->hasMany(VehiclePhoto::class);
    }

    /** Foto de portada (la más reciente) para miniaturas en listas y encabezados. */
    public function fotoPortada(): HasOne
    {
        return $this->hasOne(VehiclePhoto::class)->latestOfMany();
    }

    public function historialEstados(): HasMany
    {
        return $this->hasMany(VehicleStatusHistory::class);
    }

    public function auditoria(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function gastos(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function venta(): HasOne
    {
        return $this->hasOne(Sale::class);
    }

    public function desguace(): HasOne
    {
        return $this->hasOne(ScrapRecord::class);
    }

    /* ------------------------------- Scopes ------------------------------- */

    /**
     * Restringe la consulta a los vehículos que el rol del usuario puede ver:
     * gruero → solo los que el Admin le asignó;
     * mecánico → pendientes de revisión, en reparación y listos;
     * vendedor → listos/publicados/vendidos; admin → todos.
     */
    public function scopeVisiblePara(Builder $query, User $usuario): Builder
    {
        if ($usuario->hasRole('gruero')) {
            return $query->where('asignado_a', $usuario->id);
        }

        if ($usuario->hasRole('mecanico')) {
            return $query->whereIn('estado', [
                EstadoVehiculo::Comprado,
                EstadoVehiculo::EnReparacion,
                EstadoVehiculo::Listo,
            ]);
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

    /* ---------------------------- Rentabilidad ---------------------------- */

    /** Suma de todos los gastos registrados. */
    public function totalGastos(): float
    {
        return (float) $this->gastos()->sum('monto');
    }

    /** Inversión total = precio de compra + gastos. */
    public function inversionTotal(): float
    {
        return round((float) $this->precio_compra + $this->totalGastos(), 2);
    }

    /** Precio de venta o monto de desguace, según cómo salió del inventario. */
    public function montoRecuperado(): ?float
    {
        if ($this->estado === EstadoVehiculo::Vendido && $this->venta) {
            return (float) $this->venta->precio_venta;
        }

        if ($this->estado === EstadoVehiculo::Desguace && $this->desguace) {
            return (float) $this->desguace->monto_recibido;
        }

        return null;
    }

    /**
     * Ganancia = (precio de venta o monto de desguace) − precio de compra − gastos.
     * Null mientras el vehículo siga en inventario. Visible solo para Admin.
     */
    public function ganancia(): ?float
    {
        $recuperado = $this->montoRecuperado();

        return $recuperado === null ? null : round($recuperado - $this->inversionTotal(), 2);
    }

    /** Versión por-modelo del scope visiblePara (para policies). */
    public function esVisiblePara(User $usuario): bool
    {
        if ($usuario->hasRole('gruero')) {
            return $this->asignado_a === $usuario->id;
        }

        if ($usuario->hasRole('mecanico')) {
            return in_array($this->estado, [
                EstadoVehiculo::Comprado,
                EstadoVehiculo::EnReparacion,
                EstadoVehiculo::Listo,
            ], true);
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

<?php

namespace App\Models;

use App\Enums\EstadoTitulo;
use App\Enums\EstadoVehiculo;
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

    /**
     * Foto de portada para miniaturas en listas y encabezados: la foto del
     * vehículo marcada como portada, y si no hay ninguna marcada, la primera
     * que se subió. Las de gastos quedan excluidas, si no una foto de un
     * repuesto sería la portada.
     *
     * Antes era "la más reciente", cuando había una sola foto. Con la galería
     * eso dejaría de portada la última foto subida, aunque fuera de un rayón.
     */
    public function fotoPortada(): HasOne
    {
        // El filtro va dentro de ofMany() para que también acote la subconsulta
        // que elige la foto; si no, elegiría la foto de un gasto y luego la
        // descartaría, dejando el vehículo sin portada.
        return $this->hasOne(VehiclePhoto::class)->ofMany(
            ['es_portada' => 'max', 'id' => 'min'],
            fn ($query) => $query->whereNull('expense_id'),
        );
    }

    /** Galería: las fotos del vehículo, sin las de los gastos. */
    public function fotosVehiculo(): HasMany
    {
        return $this->hasMany(VehiclePhoto::class)->whereNull('expense_id');
    }

    /** Marca una foto de la galería como portada y desmarca las demás. */
    public function marcarPortada(VehiclePhoto $foto): void
    {
        $this->fotosVehiculo()->whereKeyNot($foto->id)->update(['es_portada' => false]);
        $foto->forceFill(['es_portada' => true])->save();
    }

    /** Documentos del vehículo (título, registro, etc.), como foto o PDF. */
    public function documentos(): HasMany
    {
        return $this->hasMany(VehicleDocument::class);
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

    /**
     * Búsqueda por marca, modelo, VIN o año.
     *
     * El término se parte en palabras y cada una tiene que aparecer en algún
     * lado: Y entre palabras, O entre columnas. Antes se comparaba la frase
     * entera contra cada columna por separado, así que "toyota corolla" no
     * encontraba nada aunque el auto estuviera ahí.
     */
    public function scopeBuscar(Builder $query, string $termino): Builder
    {
        $palabras = preg_split('/\s+/', trim($termino), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if ($palabras === []) {
            return $query;
        }

        foreach ($palabras as $palabra) {
            $query->where(function (Builder $q) use ($palabra) {
                $patron = '%'.self::escaparComodines($palabra).'%';

                $q->where('marca', 'like', $patron)
                    ->orWhere('modelo', 'like', $patron)
                    ->orWhere('vin', 'like', $patron);

                // La tarjeta muestra "2019 Honda Civic": si el año no fuera
                // buscable, copiar ese texto no encontraría nada.
                if (preg_match('/^\d{4}$/', $palabra)) {
                    $q->orWhere('anio', (int) $palabra);
                }
            });
        }

        return $query;
    }

    /**
     * Escapa los comodines de LIKE que escriba el usuario.
     * Sin esto, teclear un solo "%" devuelve el inventario completo, que es
     * exactamente lo contrario de filtrar.
     */
    private static function escaparComodines(string $valor): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $valor);
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

    /**
     * Precio de venta o monto del Junk car, según cómo salió del inventario.
     * Null si todavía no se cargó el monto (Junk car pendiente de completar).
     */
    public function montoRecuperado(): ?float
    {
        // Se comprueba el PRECIO, no solo que exista la venta: con una venta
        // sin precio, (float) null daria 0.0 y el vehiculo figuraria como
        // perdida total. Es el mismo cuidado que la rama del Junk car.
        if ($this->estado === EstadoVehiculo::Vendido && $this->venta?->precio_venta !== null) {
            return (float) $this->venta->precio_venta;
        }

        if ($this->estado === EstadoVehiculo::Desguace && $this->desguace?->monto_recibido !== null) {
            return (float) $this->desguace->monto_recibido;
        }

        return null;
    }

    /**
     * Ganancia = (precio de venta o monto del Junk car) − precio de compra − gastos.
     * Null mientras el vehículo siga en inventario, o mientras falte el monto de
     * la salida o el precio de compra (no se inventa un 0). Solo para Admin.
     */
    public function ganancia(): ?float
    {
        $recuperado = $this->montoRecuperado();

        if ($recuperado === null || $this->precio_compra === null) {
            return null;
        }

        return round($recuperado - $this->inversionTotal(), 2);
    }

    /**
     * Reglas de validación del VIN, en un solo lugar.
     *
     * Las usan el formulario del Admin y la sección de fotos y documentos de
     * la ficha. Si cada uno tuviera su copia, el día que se cambiara una la
     * otra quedaría desactualizada.
     *
     * Sin withoutTrashed() a propósito: el índice único de la base no sabe de
     * borrado lógico, así que un vehículo borrado sigue ocupando su VIN.
     * Ignorar los borrados hacía que la validación dijera "libre" y el INSERT
     * reventara con un 500.
     */
    public static function reglasVin(?int $ignorarId = null): array
    {
        return [
            'required', 'string', 'size:17', 'regex:/^[A-HJ-NPR-Z0-9]{17}$/',
            \Illuminate\Validation\Rule::unique('vehicles', 'vin')->ignore($ignorarId),
        ];
    }

    /**
     * ¿Este usuario puede registrar la venta de este vehículo ahora?
     *
     * Vive acá porque la usan dos pantallas que tienen que coincidir siempre:
     * el formulario de venta (que aparece cuando esto es cierto) y el botón
     * "Vendido" de los estados (que lleva a ese formulario). Si se
     * desincronizaran, quedaría un botón que no abre nada.
     */
    public function admiteRegistrarVenta(User $usuario): bool
    {
        return $usuario->can('registrar ventas')
            && $this->venta === null
            && in_array($this->estado, [EstadoVehiculo::Listo, EstadoVehiculo::Publicado], true);
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

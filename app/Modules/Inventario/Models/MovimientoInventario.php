<?php

declare(strict_types=1);

namespace App\Modules\Inventario\Models;

use App\Models\User;
use App\Modules\Compartido\Traits\TieneBitacora;
use App\Modules\Inventario\Enums\EstadoMovimiento;
use App\Modules\Inventario\Enums\TipoMovimiento;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * EL LIBRO MAYOR DEL ALMACEN. Una fila por cada cambio de existencia, con signo.
 *
 * No hay ninguna columna "existencia" en todo el esquema: la existencia se
 * deriva sumando estas filas, asi que el inventario no puede alejarse en
 * silencio de su historia (docs/david.md §7.2).
 *
 * Este modelo NO se crea desde un controlador ni desde otro modulo. El unico
 * que escribe aqui es ServicioMovimientoInventario, y los demas modulos le
 * piden a el la entrada o la salida. Por eso `estado` y `aplicado_en` no son
 * fillable: los pone el servicio.
 *
 * `origen_tipo` / `origen_id` apuntan al documento que causo el movimiento
 * (recepciones, pedidos, ajustes_inventario...) para poder ir del kardex al
 * papel y de vuelta.
 */
class MovimientoInventario extends Model
{
    use SoftDeletes, TieneBitacora;

    protected $table = 'movimientos_inventario';

    protected $fillable = [
        'organizacion_id',
        'producto_id',
        'almacen_id',
        'ubicacion_id',
        'tipo_movimiento',
        'cantidad',
        'costo_unitario',
        'origen_tipo',
        'origen_id',
        'lote_id',
        'numero_serie_id',
    ];

    protected $attributes = [
        'estado' => 'aplicado',
        'costo_unitario' => 0,
    ];

    protected $casts = [
        'cantidad' => 'decimal:6',
        'costo_unitario' => 'decimal:6',
        'tipo_movimiento' => TipoMovimiento::class,
        'estado' => EstadoMovimiento::class,
        'aplicado_en' => 'datetime',
    ];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    public function almacen(): BelongsTo
    {
        return $this->belongsTo(Almacen::class);
    }

    public function ubicacion(): BelongsTo
    {
        return $this->belongsTo(Ubicacion::class);
    }

    public function lote(): BelongsTo
    {
        return $this->belongsTo(Lote::class);
    }

    public function numeroSerie(): BelongsTo
    {
        return $this->belongsTo(NumeroSerie::class);
    }

    public function aplicadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'aplicado_por');
    }

    public function scopeAplicados(Builder $consulta): Builder
    {
        return $consulta->where('estado', EstadoMovimiento::Aplicado);
    }

    /** Solo los que mueven existencia fisica: sin apartados ni liberaciones. */
    public function scopeFisicos(Builder $consulta): Builder
    {
        return $consulta->whereNotIn('tipo_movimiento', [
            TipoMovimiento::Apartado->value,
            TipoMovimiento::LiberacionApartado->value,
        ]);
    }

    /** Los movimientos de un documento: kardex desde el papel. */
    public function scopeDelOrigen(Builder $consulta, string $tipo, int $id): Builder
    {
        return $consulta->where('origen_tipo', $tipo)->where('origen_id', $id);
    }

    /** El valor del renglon: cantidad (con signo) por costo. */
    public function getImporteAttribute(): float
    {
        return (float) $this->cantidad * (float) $this->costo_unitario;
    }
}

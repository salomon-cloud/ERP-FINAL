<?php

declare(strict_types=1);

namespace App\Modules\Compras\Models;

use App\Modules\Inventario\Models\Almacen;
use App\Modules\Inventario\Models\Producto;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Un renglon de orden de compra.
 *
 * `costo_unitario` y `tasa_impuesto` son FOTOS del momento en que se capturo la
 * linea: cambiar despues el costo del catalogo no reescribe una orden ya
 * enviada al proveedor.
 *
 * `cantidad_recibida` la lleva ServicioRecepcion, no el formulario, y por eso
 * no es fillable: es un hecho del almacen, no un dato que alguien teclea.
 */
class OrdenCompraLinea extends Model
{
    protected $table = 'orden_compra_lineas';

    public const UPDATED_AT = null;

    protected $fillable = [
        'orden_compra_id',
        'producto_id',
        'almacen_id',
        'descripcion',
        'cantidad',
        'costo_unitario',
        'porcentaje_descuento',
        'monto_descuento',
        'impuesto_id',
        'tasa_impuesto',
        'monto_impuesto',
        'subtotal',
        'total',
    ];

    protected $casts = [
        'cantidad' => 'decimal:6',
        'cantidad_recibida' => 'decimal:6',
        'costo_unitario' => 'decimal:2',
        'porcentaje_descuento' => 'decimal:2',
        'monto_descuento' => 'decimal:2',
        'tasa_impuesto' => 'decimal:6',
        'monto_impuesto' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function ordenCompra(): BelongsTo
    {
        return $this->belongsTo(OrdenCompra::class);
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    public function almacen(): BelongsTo
    {
        return $this->belongsTo(Almacen::class);
    }

    /** Lo que todavia falta por recibir de este renglon. */
    public function getCantidadPendienteAttribute(): float
    {
        return max((float) $this->cantidad - (float) $this->cantidad_recibida, 0.0);
    }
}

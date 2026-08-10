<?php

declare(strict_types=1);

namespace App\Modules\Ventas\Models;

use App\Modules\Inventario\Models\Producto;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Un renglon de cotizacion.
 *
 * `precio_unitario` y `tasa_impuesto` son FOTOS del momento de capturar: si
 * manana sube la lista de precios, la cotizacion que el cliente tiene en la
 * mano sigue diciendo lo mismo.
 */
class CotizacionLinea extends Model
{
    protected $table = 'cotizacion_lineas';

    public const UPDATED_AT = null;

    protected $fillable = [
        'cotizacion_id',
        'producto_id',
        'descripcion',
        'cantidad',
        'precio_unitario',
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
        'precio_unitario' => 'decimal:2',
        'porcentaje_descuento' => 'decimal:2',
        'monto_descuento' => 'decimal:2',
        'tasa_impuesto' => 'decimal:6',
        'monto_impuesto' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function cotizacion(): BelongsTo
    {
        return $this->belongsTo(Cotizacion::class);
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }
}

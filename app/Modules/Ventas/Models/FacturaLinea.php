<?php

declare(strict_types=1);

namespace App\Modules\Ventas\Models;

use App\Modules\Inventario\Models\Producto;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Un renglon de factura.
 *
 * `pedido_linea_id` conserva de donde salio, que es lo que permite saber cuanto
 * de un pedido ya se facturo cuando se factura en partes.
 */
class FacturaLinea extends Model
{
    protected $table = 'factura_lineas';

    public const UPDATED_AT = null;

    protected $fillable = [
        'factura_id',
        'pedido_linea_id',
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

    public function factura(): BelongsTo
    {
        return $this->belongsTo(Factura::class);
    }

    public function pedidoLinea(): BelongsTo
    {
        return $this->belongsTo(PedidoLinea::class, 'pedido_linea_id');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }
}

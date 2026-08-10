<?php

declare(strict_types=1);

namespace App\Modules\Compras\Models;

use App\Modules\Inventario\Models\Producto;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Un renglon de factura de proveedor.
 *
 * `orden_compra_linea_id` es lo que hace posible el cotejo de tres vias: sin
 * el, comparar lo facturado contra lo pedido y lo recibido seria adivinar por
 * nombre de producto.
 */
class FacturaProveedorLinea extends Model
{
    protected $table = 'factura_proveedor_lineas';

    public const UPDATED_AT = null;

    protected $fillable = [
        'factura_proveedor_id',
        'orden_compra_linea_id',
        'producto_id',
        'descripcion',
        'cantidad',
        'costo_unitario',
        'impuesto_id',
        'tasa_impuesto',
        'monto_impuesto',
        'subtotal',
        'total',
    ];

    protected $casts = [
        'cantidad' => 'decimal:6',
        'costo_unitario' => 'decimal:2',
        'tasa_impuesto' => 'decimal:6',
        'monto_impuesto' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function factura(): BelongsTo
    {
        return $this->belongsTo(FacturaProveedor::class, 'factura_proveedor_id');
    }

    public function ordenCompraLinea(): BelongsTo
    {
        return $this->belongsTo(OrdenCompraLinea::class, 'orden_compra_linea_id');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }
}

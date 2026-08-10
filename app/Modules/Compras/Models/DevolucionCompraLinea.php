<?php

declare(strict_types=1);

namespace App\Modules\Compras\Models;

use App\Modules\Inventario\Models\Producto;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Un renglon de devolucion a proveedor. */
class DevolucionCompraLinea extends Model
{
    protected $table = 'devolucion_compra_lineas';

    public const UPDATED_AT = null;

    protected $fillable = [
        'devolucion_id',
        'factura_proveedor_linea_id',
        'producto_id',
        'cantidad',
        'costo_unitario',
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

    public function devolucion(): BelongsTo
    {
        return $this->belongsTo(DevolucionCompra::class, 'devolucion_id');
    }

    public function facturaLinea(): BelongsTo
    {
        return $this->belongsTo(FacturaProveedorLinea::class, 'factura_proveedor_linea_id');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }
}

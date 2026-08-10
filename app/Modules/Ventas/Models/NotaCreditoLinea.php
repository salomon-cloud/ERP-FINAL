<?php

declare(strict_types=1);

namespace App\Modules\Ventas\Models;

use App\Modules\Inventario\Models\Producto;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Un renglon de nota de credito.
 *
 * `factura_linea_id` es lo que permite comprobar que no se acredita mas de lo
 * facturado de cada producto.
 */
class NotaCreditoLinea extends Model
{
    protected $table = 'nota_credito_lineas';

    public const UPDATED_AT = null;

    protected $fillable = [
        'nota_credito_id',
        'factura_linea_id',
        'producto_id',
        'descripcion',
        'cantidad',
        'precio_unitario',
        'impuesto_id',
        'tasa_impuesto',
        'monto_impuesto',
        'subtotal',
        'total',
    ];

    protected $casts = [
        'cantidad' => 'decimal:6',
        'precio_unitario' => 'decimal:2',
        'tasa_impuesto' => 'decimal:6',
        'monto_impuesto' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function notaCredito(): BelongsTo
    {
        return $this->belongsTo(NotaCredito::class, 'nota_credito_id');
    }

    public function facturaLinea(): BelongsTo
    {
        return $this->belongsTo(FacturaLinea::class, 'factura_linea_id');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }
}

<?php

declare(strict_types=1);

namespace App\Modules\Ventas\Models;

use App\Modules\Inventario\Models\Almacen;
use App\Modules\Inventario\Models\Producto;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Un renglon de pedido.
 *
 * `almacen_id` importa: es de donde se aparta y de donde saldra la mercancia.
 * Sin el, confirmar no sabria que existencia comprometer.
 *
 * `cantidad_surtida` la lleva ServicioPedido, no el formulario, y por eso no es
 * fillable: es un hecho del almacen, no un dato que alguien teclea.
 */
class PedidoLinea extends Model
{
    protected $table = 'pedido_lineas';

    public const UPDATED_AT = null;

    protected $fillable = [
        'pedido_id',
        'producto_id',
        'almacen_id',
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
        'cantidad_surtida' => 'decimal:6',
        'precio_unitario' => 'decimal:2',
        'porcentaje_descuento' => 'decimal:2',
        'monto_descuento' => 'decimal:2',
        'tasa_impuesto' => 'decimal:6',
        'monto_impuesto' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function pedido(): BelongsTo
    {
        return $this->belongsTo(Pedido::class);
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    public function almacen(): BelongsTo
    {
        return $this->belongsTo(Almacen::class);
    }

    /** Lo que falta por sacar del almacen de este renglon. */
    public function getCantidadPendienteAttribute(): float
    {
        return max((float) $this->cantidad - (float) $this->cantidad_surtida, 0.0);
    }
}

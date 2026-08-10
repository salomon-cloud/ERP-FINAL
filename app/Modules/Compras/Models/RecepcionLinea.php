<?php

declare(strict_types=1);

namespace App\Modules\Compras\Models;

use App\Modules\Inventario\Models\Lote;
use App\Modules\Inventario\Models\NumeroSerie;
use App\Modules\Inventario\Models\Producto;
use App\Modules\Inventario\Models\Ubicacion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Lo que realmente llego de un renglon de la orden.
 *
 * Lleva `lote_id` y `ubicacion_id` porque el momento de recibir es el unico en
 * que se sabe con certeza que lote entro y en que anaquel se guardo. Pedirlo
 * despues seria adivinar.
 *
 * `costo_unitario` es la foto del costo de la orden, no el del catalogo: lo que
 * entra al inventario vale lo que se acordo pagar por ello.
 */
class RecepcionLinea extends Model
{
    protected $table = 'recepcion_lineas';

    public const UPDATED_AT = null;

    protected $fillable = [
        'recepcion_id',
        'orden_compra_linea_id',
        'producto_id',
        'ubicacion_id',
        'cantidad_recibida',
        'costo_unitario',
        'lote_id',
        'numero_serie_id',
    ];

    protected $casts = [
        'cantidad_recibida' => 'decimal:6',
        'costo_unitario' => 'decimal:2',
    ];

    public function recepcion(): BelongsTo
    {
        return $this->belongsTo(Recepcion::class);
    }

    public function ordenCompraLinea(): BelongsTo
    {
        return $this->belongsTo(OrdenCompraLinea::class, 'orden_compra_linea_id');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
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
}

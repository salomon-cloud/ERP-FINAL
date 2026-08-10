<?php

declare(strict_types=1);

namespace App\Modules\Inventario\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Un renglon de traspaso: que producto y cuanto.
 *
 * `costo_unitario` es la foto del costo con que sale del origen, para que la
 * entrada al destino valga lo mismo y el traspaso no genere ni destruya valor.
 */
class TraspasoLinea extends Model
{
    protected $table = 'traspaso_lineas';

    public const UPDATED_AT = null;

    protected $fillable = [
        'traspaso_id',
        'producto_id',
        'cantidad',
        'costo_unitario',
        'lote_id',
    ];

    protected $casts = [
        'cantidad' => 'decimal:6',
        'costo_unitario' => 'decimal:6',
    ];

    public function traspaso(): BelongsTo
    {
        return $this->belongsTo(Traspaso::class);
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    public function lote(): BelongsTo
    {
        return $this->belongsTo(Lote::class);
    }
}

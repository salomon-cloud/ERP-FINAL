<?php

declare(strict_types=1);

namespace App\Modules\Compras\Models;

use App\Modules\Inventario\Models\Producto;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Un renglon de requisicion: que se necesita y cuanto.
 *
 * Sin precio a proposito: quien pide no cotiza. `proveedor_sugerido_id` es una
 * sugerencia de quien conoce el articulo, no una decision de compra.
 */
class RequisicionLinea extends Model
{
    protected $table = 'requisicion_lineas';

    public const UPDATED_AT = null;

    protected $fillable = [
        'requisicion_id',
        'producto_id',
        'cantidad_solicitada',
        'proveedor_sugerido_id',
        'notas',
    ];

    protected $casts = [
        'cantidad_solicitada' => 'decimal:6',
    ];

    public function requisicion(): BelongsTo
    {
        return $this->belongsTo(Requisicion::class);
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    public function proveedorSugerido(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_sugerido_id');
    }
}

<?php

declare(strict_types=1);

namespace App\Modules\Inventario\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Los codigos de barras de un producto. Son varios a proposito: el mismo
 * articulo llega con el codigo del fabricante, el de la caja y el interno.
 *
 * La tabla solo lleva created_at, asi que se apaga updated_at.
 */
class CodigoBarras extends Model
{
    protected $table = 'codigos_barras';

    public const UPDATED_AT = null;

    protected $fillable = [
        'producto_id',
        'codigo',
        'es_principal',
    ];

    protected $casts = [
        'es_principal' => 'boolean',
    ];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }
}

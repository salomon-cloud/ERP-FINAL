<?php

declare(strict_types=1);

namespace App\Modules\Ventas\Models;

use App\Modules\Inventario\Models\Producto;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * El precio de un producto en una lista, a partir de cierta cantidad.
 *
 * `cantidad_minima` es lo que permite el precio por volumen: la misma lista
 * puede decir 100 pesos a partir de 1 pieza y 85 a partir de 50. Resolver el
 * precio es tomar el escalon mas alto que la cantidad pedida alcanza.
 */
class ListaPrecioItem extends Model
{
    protected $table = 'lista_precio_items';

    protected $fillable = [
        'lista_precio_id',
        'producto_id',
        'cantidad_minima',
        'precio',
    ];

    protected $attributes = [
        'cantidad_minima' => 1,
    ];

    protected $casts = [
        'cantidad_minima' => 'decimal:6',
        'precio' => 'decimal:2',
    ];

    public function lista(): BelongsTo
    {
        return $this->belongsTo(ListaPrecio::class, 'lista_precio_id');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }
}

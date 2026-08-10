<?php

declare(strict_types=1);

namespace App\Modules\Inventario\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Un renglon de conteo.
 *
 * `cantidad_esperada` es la foto que se tomo al iniciar el conteo; si se
 * recalculara al cerrar, la diferencia se moveria sola mientras la gente cuenta
 * y el conteo no probaria nada. `diferencia` la mantiene el observer.
 */
class ConteoLinea extends Model
{
    protected $table = 'conteo_lineas';

    protected $fillable = [
        'conteo_id',
        'producto_id',
        'cantidad_esperada',
        'cantidad_contada',
        // La calcula ServicioConteo, nunca el formulario: el FormRequest de
        // captura solo acepta `cantidad_contada`.
        'diferencia',
    ];

    protected $casts = [
        'cantidad_esperada' => 'decimal:6',
        'cantidad_contada' => 'decimal:6',
        'diferencia' => 'decimal:6',
    ];

    public function conteo(): BelongsTo
    {
        return $this->belongsTo(ConteoInventario::class, 'conteo_id');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }
}

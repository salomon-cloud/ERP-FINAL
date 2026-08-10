<?php

declare(strict_types=1);

namespace App\Modules\Inventario\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Un renglon de ajuste. `diferencia` va CON SIGNO: positivo si sobra fisico,
 * negativo si falta. El CHECK de la tabla prohibe el cero, porque un ajuste de
 * cero no ajusta nada.
 */
class AjusteLinea extends Model
{
    protected $table = 'ajuste_lineas';

    public const UPDATED_AT = null;

    protected $fillable = [
        'ajuste_id',
        'producto_id',
        'almacen_id',
        'ubicacion_id',
        'diferencia',
        'costo_unitario',
        'motivo',
    ];

    protected $casts = [
        'diferencia' => 'decimal:6',
        'costo_unitario' => 'decimal:6',
    ];

    public function ajuste(): BelongsTo
    {
        return $this->belongsTo(AjusteInventario::class, 'ajuste_id');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    public function almacen(): BelongsTo
    {
        return $this->belongsTo(Almacen::class);
    }

    public function ubicacion(): BelongsTo
    {
        return $this->belongsTo(Ubicacion::class);
    }
}

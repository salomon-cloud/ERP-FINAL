<?php

declare(strict_types=1);

namespace App\Modules\Compras\Models;

use App\Modules\Compartido\Traits\SumaTotalesDeLineas;
use App\Modules\Compartido\Traits\TieneBitacora;
use App\Modules\Compartido\Traits\TieneCamposAuditoria;
use App\Modules\Compras\Enums\EstadoOrdenCompra;
use App\Modules\Compras\Observers\ObservadorOrdenCompra;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * El compromiso de compra con un proveedor.
 *
 * `version_fila` es bloqueo optimista: confirmar una orden que alguien mas
 * acaba de modificar comprometeria cantidades o precios distintos de los que se
 * vieron en pantalla.
 *
 * Los totales los mantiene SumaTotalesDeLineas; nadie los captura.
 */
#[ObservedBy([ObservadorOrdenCompra::class])]
class OrdenCompra extends Model
{
    use SoftDeletes, SumaTotalesDeLineas, TieneBitacora, TieneCamposAuditoria;

    protected $table = 'ordenes_compra';

    protected $fillable = [
        'organizacion_id',
        'proveedor_id',
        'requisicion_id',
        'fecha',
        'fecha_entrega',
        'moneda',
        'notas',
    ];

    protected $attributes = [
        'estado' => 'borrador',
        'moneda' => 'MXN',
    ];

    protected $casts = [
        'fecha' => 'date',
        'fecha_entrega' => 'date',
        'estado' => EstadoOrdenCompra::class,
        'subtotal' => 'decimal:2',
        'total_descuento' => 'decimal:2',
        'total_impuesto' => 'decimal:2',
        'total' => 'decimal:2',
        'version_fila' => 'integer',
    ];

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function requisicion(): BelongsTo
    {
        return $this->belongsTo(Requisicion::class);
    }

    public function lineas(): HasMany
    {
        return $this->hasMany(OrdenCompraLinea::class);
    }

    public function recepciones(): HasMany
    {
        return $this->hasMany(Recepcion::class);
    }

    public function facturas(): HasMany
    {
        return $this->hasMany(FacturaProveedor::class);
    }

    public function scopeBuscar(Builder $consulta, ?string $termino): Builder
    {
        if (blank($termino)) {
            return $consulta;
        }

        return $consulta->where(fn (Builder $filtro) => $filtro
            ->where('numero_orden', 'like', '%'.$termino.'%')
            ->orWhereHas('proveedor', fn (Builder $proveedor) => $proveedor->buscar($termino)));
    }

    /** Verdadero cuando ya llego todo lo que se pidio. */
    public function estaCompletamenteRecibida(): bool
    {
        return $this->lineas->every(
            fn (OrdenCompraLinea $linea) => (float) $linea->cantidad_recibida + 0.000001 >= (float) $linea->cantidad
        );
    }

    /** Verdadero cuando llego algo, aunque no sea todo. */
    public function tieneAlgoRecibido(): bool
    {
        return $this->lineas->contains(fn (OrdenCompraLinea $linea) => (float) $linea->cantidad_recibida > 0);
    }
}

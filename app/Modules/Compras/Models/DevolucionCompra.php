<?php

declare(strict_types=1);

namespace App\Modules\Compras\Models;

use App\Modules\Compartido\Traits\SumaTotalesDeLineas;
use App\Modules\Compartido\Traits\TieneBitacora;
use App\Modules\Compartido\Traits\TieneCamposAuditoria;
use App\Modules\Compras\Enums\EstadoDevolucionCompra;
use App\Modules\Compras\Enums\MotivoDevolucionCompra;
use App\Modules\Compras\Observers\ObservadorDevolucionCompra;
use App\Modules\Inventario\Models\Almacen;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Mercancia que se le regresa al proveedor.
 *
 * Aplicarla saca la mercancia del almacen (`devolucion_salida`) y genera el
 * cargo a favor con el proveedor. Cuelga de la FACTURA y no de la recepcion
 * porque lo que se reclama es dinero facturado, no solo mercancia.
 *
 * La tabla no guarda el almacen de salida, asi que se toma el de la recepcion
 * de la factura -- ver ServicioDevolucionCompra.
 */
#[ObservedBy([ObservadorDevolucionCompra::class])]
class DevolucionCompra extends Model
{
    use SoftDeletes, SumaTotalesDeLineas, TieneBitacora, TieneCamposAuditoria;

    protected $table = 'devoluciones_compra';

    protected $fillable = [
        'organizacion_id',
        'factura_proveedor_id',
        'proveedor_id',
        'motivo',
        'fecha',
    ];

    protected $attributes = [
        'estado' => 'borrador',
    ];

    protected $casts = [
        'fecha' => 'date',
        'motivo' => MotivoDevolucionCompra::class,
        'estado' => EstadoDevolucionCompra::class,
        'subtotal' => 'decimal:2',
        'total_impuesto' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    /** La tabla no tiene columna total_descuento. */
    protected function llevaTotalDescuento(): bool
    {
        return false;
    }

    public function factura(): BelongsTo
    {
        return $this->belongsTo(FacturaProveedor::class, 'factura_proveedor_id');
    }

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function lineas(): HasMany
    {
        return $this->hasMany(DevolucionCompraLinea::class, 'devolucion_id');
    }

    public function scopeBuscar(Builder $consulta, ?string $termino): Builder
    {
        if (blank($termino)) {
            return $consulta;
        }

        return $consulta->where(fn (Builder $filtro) => $filtro
            ->where('numero_devolucion', 'like', '%'.$termino.'%')
            ->orWhereHas('proveedor', fn (Builder $proveedor) => $proveedor->buscar($termino)));
    }

    /**
     * De que almacen sale la mercancia devuelta.
     *
     * La tabla no lo guarda, asi que se deduce de la recepcion de la factura y,
     * si no la tiene, de la recepcion mas reciente de su orden de compra: la
     * mercancia sale de donde entro.
     */
    public function almacenDeSalida(): ?Almacen
    {
        $factura = $this->factura;

        if ($factura?->recepcion !== null) {
            return $factura->recepcion->almacen;
        }

        return $factura?->ordenCompra?->recepciones()
            ->where('estado', 'aplicada')
            ->latest('id')
            ->first()?->almacen;
    }
}

<?php

declare(strict_types=1);

namespace App\Modules\Compras\Models;

use App\Modules\Compartido\Traits\SumaTotalesDeLineas;
use App\Modules\Compartido\Traits\TieneBitacora;
use App\Modules\Compartido\Traits\TieneCamposAuditoria;
use App\Modules\Compras\Enums\EstadoFacturaProveedor;
use App\Modules\Compras\Observers\ObservadorFacturaProveedor;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * La factura que manda el proveedor: la cuenta por pagar.
 *
 * `numero_factura` es un folio propio (FP-000001) y no el del proveedor: el
 * suyo es un dato externo que puede repetirse entre proveedores distintos, asi
 * que no sirve como identificador unico. Se anota en `notas`.
 *
 * `total_pagado` lo lleva ServicioPago sumando los pagos aplicados; no es un
 * campo que alguien capture.
 */
#[ObservedBy([ObservadorFacturaProveedor::class])]
class FacturaProveedor extends Model
{
    use SoftDeletes, SumaTotalesDeLineas, TieneBitacora, TieneCamposAuditoria;

    protected $table = 'facturas_proveedor';

    protected $fillable = [
        'organizacion_id',
        'proveedor_id',
        'orden_compra_id',
        'recepcion_id',
        'periodo_fiscal_id',
        'fecha',
        'fecha_vencimiento',
        'moneda',
        'notas',
    ];

    protected $attributes = [
        'estado' => 'borrador',
        'moneda' => 'MXN',
    ];

    protected $casts = [
        'fecha' => 'date',
        'fecha_vencimiento' => 'date',
        'estado' => EstadoFacturaProveedor::class,
        'subtotal' => 'decimal:2',
        'total_impuesto' => 'decimal:2',
        'total' => 'decimal:2',
        'total_pagado' => 'decimal:2',
        'version_fila' => 'integer',
    ];

    /** La tabla no tiene columna total_descuento. */
    protected function llevaTotalDescuento(): bool
    {
        return false;
    }

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function ordenCompra(): BelongsTo
    {
        return $this->belongsTo(OrdenCompra::class);
    }

    public function recepcion(): BelongsTo
    {
        return $this->belongsTo(Recepcion::class);
    }

    public function lineas(): HasMany
    {
        return $this->hasMany(FacturaProveedorLinea::class, 'factura_proveedor_id');
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class, 'factura_proveedor_id');
    }

    public function devoluciones(): HasMany
    {
        return $this->hasMany(DevolucionCompra::class, 'factura_proveedor_id');
    }

    public function scopeBuscar(Builder $consulta, ?string $termino): Builder
    {
        if (blank($termino)) {
            return $consulta;
        }

        return $consulta->where(fn (Builder $filtro) => $filtro
            ->where('numero_factura', 'like', '%'.$termino.'%')
            ->orWhereHas('proveedor', fn (Builder $proveedor) => $proveedor->buscar($termino)));
    }

    public function getSaldoAttribute(): float
    {
        return round((float) $this->total - (float) $this->total_pagado, 2);
    }

    /**
     * Vencida es DERIVADO, no un estado guardado.
     *
     * El CHECK de la tabla no incluye 'vencida' para facturas de proveedor, y
     * aunque lo incluyera seria un error guardarlo: dependeria de la fecha de
     * hoy y habria que refrescarlo con un cron.
     */
    public function getEstaVencidaAttribute(): bool
    {
        return $this->fecha_vencimiento !== null
            && $this->fecha_vencimiento->isPast()
            && $this->saldo > 0
            && $this->estado->admitePago();
    }
}

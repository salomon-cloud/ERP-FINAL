<?php

declare(strict_types=1);

namespace App\Modules\Ventas\Models;

use App\Modules\Compartido\Models\Catalogo;
use App\Modules\Compartido\Traits\SumaTotalesDeLineas;
use App\Modules\Compartido\Traits\TieneBitacora;
use App\Modules\Compartido\Traits\TieneCamposAuditoria;
use App\Modules\Ventas\Enums\EstadoFactura;
use App\Modules\Ventas\Observers\ObservadorFactura;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * La cuenta por cobrar.
 *
 * Emitirla contabiliza ingreso, impuesto y CxC en Finanzas y la vuelve
 * inmutable: a partir de ahi se corrige con una nota de credito, no editandola.
 *
 * `total_cobrado` lo lleva ServicioCobro sumando los cobros aplicados.
 */
#[ObservedBy([ObservadorFactura::class])]
class Factura extends Model
{
    use SoftDeletes, SumaTotalesDeLineas, TieneBitacora, TieneCamposAuditoria;

    protected $table = 'facturas';

    protected $fillable = [
        'organizacion_id',
        'pedido_id',
        'cliente_id',
        'periodo_fiscal_id',
        'fecha_emision',
        'fecha_vencimiento',
        'condicion_pago_id',
        'moneda',
        'notas',
    ];

    protected $attributes = [
        'estado' => 'borrador',
        'moneda' => 'MXN',
    ];

    protected $casts = [
        'fecha_emision' => 'date',
        'fecha_vencimiento' => 'date',
        'estado' => EstadoFactura::class,
        'subtotal' => 'decimal:2',
        'total_descuento' => 'decimal:2',
        'total_impuesto' => 'decimal:2',
        'total' => 'decimal:2',
        'total_cobrado' => 'decimal:2',
        'version_fila' => 'integer',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function pedido(): BelongsTo
    {
        return $this->belongsTo(Pedido::class);
    }

    public function condicionPago(): BelongsTo
    {
        return $this->belongsTo(Catalogo::class, 'condicion_pago_id');
    }

    public function lineas(): HasMany
    {
        return $this->hasMany(FacturaLinea::class);
    }

    public function cobros(): HasMany
    {
        return $this->hasMany(Cobro::class);
    }

    public function notasCredito(): HasMany
    {
        return $this->hasMany(NotaCredito::class);
    }

    public function scopeBuscar(Builder $consulta, ?string $termino): Builder
    {
        if (blank($termino)) {
            return $consulta;
        }

        return $consulta->where(fn (Builder $filtro) => $filtro
            ->where('numero_factura', 'like', '%'.$termino.'%')
            ->orWhereHas('cliente', fn (Builder $cliente) => $cliente->buscar($termino)));
    }

    public function getSaldoAttribute(): float
    {
        return round((float) $this->total - (float) $this->total_cobrado, 2);
    }

    /**
     * Vencida es DERIVADO. Ver el comentario de EstadoFactura: guardarlo
     * exigiria un cron nocturno que lo refrescara.
     */
    public function getEstaVencidaAttribute(): bool
    {
        return $this->fecha_vencimiento !== null
            && $this->fecha_vencimiento->isPast()
            && $this->saldo > 0
            && $this->estado->admiteCobro();
    }

    /** Lo acreditado con notas de credito emitidas. */
    public function getTotalAcreditadoAttribute(): float
    {
        return (float) $this->notasCredito()->where('estado', 'emitida')->sum('total');
    }
}

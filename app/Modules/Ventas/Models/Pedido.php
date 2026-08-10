<?php

declare(strict_types=1);

namespace App\Modules\Ventas\Models;

use App\Modules\Compartido\Traits\SumaTotalesDeLineas;
use App\Modules\Compartido\Traits\TieneBitacora;
use App\Modules\Compartido\Traits\TieneCamposAuditoria;
use App\Modules\Ventas\Enums\EstadoPedido;
use App\Modules\Ventas\Observers\ObservadorPedido;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * La venta confirmada. Es el documento que compromete existencia.
 *
 * `version_fila` es bloqueo optimista: confirmar un pedido que alguien mas
 * acaba de modificar apartaria cantidades distintas de las que se vieron.
 */
#[ObservedBy([ObservadorPedido::class])]
class Pedido extends Model
{
    use SoftDeletes, SumaTotalesDeLineas, TieneBitacora, TieneCamposAuditoria;

    protected $table = 'pedidos';

    protected $fillable = [
        'organizacion_id',
        'cotizacion_id',
        'cliente_id',
        'lista_precio_id',
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
        'estado' => EstadoPedido::class,
        'subtotal' => 'decimal:2',
        'total_descuento' => 'decimal:2',
        'total_impuesto' => 'decimal:2',
        'total' => 'decimal:2',
        'version_fila' => 'integer',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function cotizacion(): BelongsTo
    {
        return $this->belongsTo(Cotizacion::class);
    }

    public function listaPrecio(): BelongsTo
    {
        return $this->belongsTo(ListaPrecio::class, 'lista_precio_id');
    }

    public function lineas(): HasMany
    {
        return $this->hasMany(PedidoLinea::class);
    }

    public function facturas(): HasMany
    {
        return $this->hasMany(Factura::class);
    }

    public function scopeBuscar(Builder $consulta, ?string $termino): Builder
    {
        if (blank($termino)) {
            return $consulta;
        }

        return $consulta->where(fn (Builder $filtro) => $filtro
            ->where('numero_pedido', 'like', '%'.$termino.'%')
            ->orWhereHas('cliente', fn (Builder $cliente) => $cliente->buscar($termino)));
    }

    /** Verdadero cuando ya salio del almacen todo lo que se pidio. */
    public function estaCompletamenteSurtido(): bool
    {
        return $this->lineas->every(
            fn (PedidoLinea $linea) => (float) $linea->cantidad_surtida + 0.000001 >= (float) $linea->cantidad
        );
    }

    /** Lo que ya se facturo de este pedido, sin contar facturas canceladas. */
    public function getTotalFacturadoAttribute(): float
    {
        return (float) $this->facturas()
            ->where('estado', '<>', 'cancelada')
            ->sum('total');
    }
}

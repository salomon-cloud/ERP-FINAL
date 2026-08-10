<?php

declare(strict_types=1);

namespace App\Modules\Ventas\Models;

use App\Modules\Compartido\Traits\SumaTotalesDeLineas;
use App\Modules\Compartido\Traits\TieneBitacora;
use App\Modules\Compartido\Traits\TieneCamposAuditoria;
use App\Modules\Ventas\Enums\EstadoCotizacion;
use App\Modules\Ventas\Observers\ObservadorCotizacion;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Una oferta con vigencia. No compromete existencia ni dinero: eso empieza en
 * el pedido.
 */
#[ObservedBy([ObservadorCotizacion::class])]
class Cotizacion extends Model
{
    use SoftDeletes, SumaTotalesDeLineas, TieneBitacora, TieneCamposAuditoria;

    protected $table = 'cotizaciones';

    protected $fillable = [
        'organizacion_id',
        'cliente_id',
        'lista_precio_id',
        'fecha',
        'vigencia',
        'moneda',
        'notas',
    ];

    protected $attributes = [
        'estado' => 'borrador',
        'moneda' => 'MXN',
    ];

    protected $casts = [
        'fecha' => 'date',
        'vigencia' => 'date',
        'estado' => EstadoCotizacion::class,
        'subtotal' => 'decimal:2',
        'total_descuento' => 'decimal:2',
        'total_impuesto' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function listaPrecio(): BelongsTo
    {
        return $this->belongsTo(ListaPrecio::class, 'lista_precio_id');
    }

    public function lineas(): HasMany
    {
        return $this->hasMany(CotizacionLinea::class);
    }

    public function pedidos(): HasMany
    {
        return $this->hasMany(Pedido::class);
    }

    public function scopeBuscar(Builder $consulta, ?string $termino): Builder
    {
        if (blank($termino)) {
            return $consulta;
        }

        return $consulta->where(fn (Builder $filtro) => $filtro
            ->where('numero_cotizacion', 'like', '%'.$termino.'%')
            ->orWhereHas('cliente', fn (Builder $cliente) => $cliente->buscar($termino)));
    }

    /**
     * Vencida es DERIVADO, no un estado guardado.
     *
     * Una cotizacion enviada cuya vigencia ya paso esta vencida desde el momento
     * en que la fecha cambia, sin que nadie tenga que correr un proceso. Solo
     * aplica a las enviadas: un borrador sin mandar no vence, y una aceptada ya
     * cumplio su proposito.
     */
    public function getEstaVencidaAttribute(): bool
    {
        return $this->estado === EstadoCotizacion::Enviada
            && $this->vigencia !== null
            && $this->vigencia->isPast();
    }
}

<?php

declare(strict_types=1);

namespace App\Modules\Ventas\Models;

use App\Modules\Compartido\Traits\TieneBitacora;
use App\Modules\Compartido\Traits\TieneCamposAuditoria;
use App\Modules\Ventas\Enums\EstadoCobro;
use App\Modules\Ventas\Enums\FormaPagoCobro;
use App\Modules\Ventas\Observers\ObservadorCobro;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Un cobro al cliente.
 *
 * `factura_id` puede ir vacio: es un cobro a cuenta, dinero que el cliente
 * adelanta y que se aplicara despues a una factura concreta.
 */
#[ObservedBy([ObservadorCobro::class])]
class Cobro extends Model
{
    use SoftDeletes, TieneBitacora, TieneCamposAuditoria;

    protected $table = 'cobros';

    protected $fillable = [
        'organizacion_id',
        'cliente_id',
        'factura_id',
        'fecha',
        'monto',
        'forma_pago',
        'referencia',
        'cuenta_bancaria_id',
    ];

    protected $attributes = [
        'estado' => 'borrador',
        'forma_pago' => 'efectivo',
    ];

    protected $casts = [
        'fecha' => 'date',
        'monto' => 'decimal:2',
        'forma_pago' => FormaPagoCobro::class,
        'estado' => EstadoCobro::class,
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function factura(): BelongsTo
    {
        return $this->belongsTo(Factura::class);
    }

    public function scopeBuscar(Builder $consulta, ?string $termino): Builder
    {
        if (blank($termino)) {
            return $consulta;
        }

        return $consulta->where(fn (Builder $filtro) => $filtro
            ->where('numero_cobro', 'like', '%'.$termino.'%')
            ->orWhere('referencia', 'like', '%'.$termino.'%')
            ->orWhereHas('cliente', fn (Builder $cliente) => $cliente->buscar($termino)));
    }
}

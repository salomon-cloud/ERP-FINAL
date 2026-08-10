<?php

declare(strict_types=1);

namespace App\Modules\Compras\Models;

use App\Modules\Compartido\Traits\TieneBitacora;
use App\Modules\Compartido\Traits\TieneCamposAuditoria;
use App\Modules\Compras\Enums\EstadoPago;
use App\Modules\Compras\Enums\FormaPagoProveedor;
use App\Modules\Compras\Observers\ObservadorPago;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Un pago a proveedor.
 *
 * `factura_proveedor_id` puede ir vacio: es un pago a cuenta, un anticipo que se
 * aplicara despues. Aplicar un pago sin factura solo mueve el banco; no hay
 * saldo que bajar hasta que se le asigne una.
 */
#[ObservedBy([ObservadorPago::class])]
class Pago extends Model
{
    use SoftDeletes, TieneBitacora, TieneCamposAuditoria;

    protected $table = 'pagos';

    protected $fillable = [
        'organizacion_id',
        'proveedor_id',
        'factura_proveedor_id',
        'fecha',
        'monto',
        'forma_pago',
        'referencia',
        'cuenta_bancaria_id',
    ];

    protected $attributes = [
        'estado' => 'borrador',
        'forma_pago' => 'transferencia',
    ];

    protected $casts = [
        'fecha' => 'date',
        'monto' => 'decimal:2',
        'forma_pago' => FormaPagoProveedor::class,
        'estado' => EstadoPago::class,
    ];

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function factura(): BelongsTo
    {
        return $this->belongsTo(FacturaProveedor::class, 'factura_proveedor_id');
    }

    public function scopeBuscar(Builder $consulta, ?string $termino): Builder
    {
        if (blank($termino)) {
            return $consulta;
        }

        return $consulta->where(fn (Builder $filtro) => $filtro
            ->where('numero_pago', 'like', '%'.$termino.'%')
            ->orWhere('referencia', 'like', '%'.$termino.'%')
            ->orWhereHas('proveedor', fn (Builder $proveedor) => $proveedor->buscar($termino)));
    }
}

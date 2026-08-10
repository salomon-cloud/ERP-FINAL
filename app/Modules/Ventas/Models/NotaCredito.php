<?php

declare(strict_types=1);

namespace App\Modules\Ventas\Models;

use App\Modules\Compartido\Traits\SumaTotalesDeLineas;
use App\Modules\Compartido\Traits\TieneBitacora;
use App\Modules\Compartido\Traits\TieneCamposAuditoria;
use App\Modules\Ventas\Enums\EstadoNotaCredito;
use App\Modules\Ventas\Enums\MotivoNotaCredito;
use App\Modules\Ventas\Observers\ObservadorNotaCredito;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * La correccion de una factura ya emitida.
 *
 * Una factura emitida NO se edita: se corrige con una nota de credito, y las
 * dos quedan. Es lo que hace que el ingreso reportado se pueda auditar.
 *
 * El MOTIVO decide si ademas mueve inventario: solo `devolucion` regresa
 * mercancia al almacen (ver MotivoNotaCredito).
 */
#[ObservedBy([ObservadorNotaCredito::class])]
class NotaCredito extends Model
{
    use SoftDeletes, SumaTotalesDeLineas, TieneBitacora, TieneCamposAuditoria;

    protected $table = 'notas_credito';

    protected $fillable = [
        'organizacion_id',
        'factura_id',
        'cliente_id',
        'motivo',
        'fecha_emision',
    ];

    protected $attributes = [
        'estado' => 'borrador',
    ];

    protected $casts = [
        'fecha_emision' => 'date',
        'motivo' => MotivoNotaCredito::class,
        'estado' => EstadoNotaCredito::class,
        'subtotal' => 'decimal:2',
        'total_impuesto' => 'decimal:2',
        'total' => 'decimal:2',
        'version_fila' => 'integer',
    ];

    /** La tabla no tiene columna total_descuento. */
    protected function llevaTotalDescuento(): bool
    {
        return false;
    }

    public function factura(): BelongsTo
    {
        return $this->belongsTo(Factura::class);
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function lineas(): HasMany
    {
        return $this->hasMany(NotaCreditoLinea::class, 'nota_credito_id');
    }

    public function scopeBuscar(Builder $consulta, ?string $termino): Builder
    {
        if (blank($termino)) {
            return $consulta;
        }

        return $consulta->where(fn (Builder $filtro) => $filtro
            ->where('numero_nota', 'like', '%'.$termino.'%')
            ->orWhereHas('cliente', fn (Builder $cliente) => $cliente->buscar($termino)));
    }
}

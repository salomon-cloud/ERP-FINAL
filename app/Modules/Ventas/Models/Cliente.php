<?php

declare(strict_types=1);

namespace App\Modules\Ventas\Models;

use App\Modules\Compartido\Enums\EstadoActivacion;
use App\Modules\Compartido\Models\Catalogo;
use App\Modules\Compartido\Traits\TieneBitacora;
use App\Modules\Compartido\Traits\TieneCamposAuditoria;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

/**
 * EL catalogo de clientes. Ventas es su unico dueno; CRM los referencia para
 * oportunidades e historial y nunca guarda una segunda copia.
 *
 * El SALDO no es una columna: se deriva de las facturas emitidas menos lo
 * cobrado, por la misma razon que la existencia se deriva de los movimientos.
 * Una columna de saldo se desincroniza en cuanto alguien cancela un cobro.
 */
class Cliente extends Model
{
    use SoftDeletes, TieneBitacora, TieneCamposAuditoria;

    protected $table = 'clientes';

    protected $fillable = [
        'organizacion_id',
        'codigo',
        'nombre',
        'razon_social',
        'rfc',
        'correo',
        'telefono',
        'direccion',
        'limite_credito',
        'condicion_pago_id',
        'lista_precio_id',
        'moneda',
        'estado',
    ];

    protected $attributes = [
        'limite_credito' => 0,
        'moneda' => 'MXN',
        'estado' => 'activo',
    ];

    protected $casts = [
        'limite_credito' => 'decimal:2',
        'estado' => EstadoActivacion::class,
    ];

    /** Fila de `catalogos` del grupo condiciones_pago; su `valor` son los dias. */
    public function condicionPago(): BelongsTo
    {
        return $this->belongsTo(Catalogo::class, 'condicion_pago_id');
    }

    public function listaPrecio(): BelongsTo
    {
        return $this->belongsTo(ListaPrecio::class, 'lista_precio_id');
    }

    public function cotizaciones(): HasMany
    {
        return $this->hasMany(Cotizacion::class);
    }

    public function pedidos(): HasMany
    {
        return $this->hasMany(Pedido::class);
    }

    public function facturas(): HasMany
    {
        return $this->hasMany(Factura::class);
    }

    public function cobros(): HasMany
    {
        return $this->hasMany(Cobro::class);
    }

    public function notasCredito(): HasMany
    {
        return $this->hasMany(NotaCredito::class);
    }

    public function scopeActivos(Builder $consulta): Builder
    {
        return $consulta->where('estado', EstadoActivacion::Activo);
    }

    public function scopeBuscar(Builder $consulta, ?string $termino): Builder
    {
        if (blank($termino)) {
            return $consulta;
        }

        return $consulta->where(function (Builder $filtro) use ($termino): void {
            $filtro->where('nombre', 'like', '%'.$termino.'%')
                ->orWhere('codigo', 'like', '%'.$termino.'%')
                ->orWhere('razon_social', 'like', '%'.$termino.'%')
                ->orWhere('rfc', 'like', '%'.$termino.'%')
                ->orWhere('correo', 'like', '%'.$termino.'%');
        });
    }

    /** Lo que nos debe: facturas emitidas o a medio cobrar. */
    public function getSaldoPendienteAttribute(): float
    {
        return (float) $this->facturas()
            ->whereIn('estado', ['emitida', 'cobrada_parcial', 'vencida'])
            ->selectRaw('COALESCE(SUM(total - total_cobrado), 0) as saldo')
            ->value('saldo');
    }

    /** Cuanto le queda de credito antes de topar su limite. */
    public function getCreditoDisponibleAttribute(): float
    {
        return round((float) $this->limite_credito - $this->saldo_pendiente, 2);
    }

    /**
     * La ficha 360 del cliente, tal como la define la vista v_historial_cliente.
     *
     * Se lee la VISTA y no se rearman las sumas aqui, para que Ventas y CRM
     * pinten exactamente las mismas cifras (docs/david.md §27).
     */
    public function historial(): ?object
    {
        return DB::table('v_historial_cliente')->where('cliente_id', $this->getKey())->first();
    }

    public function getEtiquetaAttribute(): string
    {
        return $this->codigo.' - '.$this->nombre;
    }
}

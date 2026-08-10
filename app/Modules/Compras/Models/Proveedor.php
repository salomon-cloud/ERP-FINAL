<?php

declare(strict_types=1);

namespace App\Modules\Compras\Models;

use App\Modules\Compartido\Enums\EstadoActivacion;
use App\Modules\Compartido\Models\Catalogo;
use App\Modules\Compartido\Traits\TieneBitacora;
use App\Modules\Compartido\Traits\TieneCamposAuditoria;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * El catalogo de proveedores. Es el espejo de `clientes` del lado de la compra.
 *
 * Un proveedor con historia NO se borra: las llaves foraneas de ordenes,
 * facturas y pagos son RESTRICT, y con razon -- borrarlo dejaria documentos
 * apuntando al vacio. Se marca inactivo.
 */
class Proveedor extends Model
{
    use SoftDeletes, TieneBitacora, TieneCamposAuditoria;

    protected $table = 'proveedores';

    protected $fillable = [
        'organizacion_id',
        'codigo',
        'nombre',
        'razon_social',
        'rfc',
        'contacto',
        'correo',
        'telefono',
        'direccion',
        'condicion_pago_id',
        'moneda',
        'estado',
    ];

    protected $attributes = [
        'moneda' => 'MXN',
        'estado' => 'activo',
    ];

    protected $casts = [
        'estado' => EstadoActivacion::class,
    ];

    /** Fila de `catalogos` del grupo condiciones_pago; su `valor` son los dias. */
    public function condicionPago(): BelongsTo
    {
        return $this->belongsTo(Catalogo::class, 'condicion_pago_id');
    }

    public function requisicionesSugeridas(): HasMany
    {
        return $this->hasMany(RequisicionLinea::class, 'proveedor_sugerido_id');
    }

    public function ordenes(): HasMany
    {
        return $this->hasMany(OrdenCompra::class);
    }

    public function facturas(): HasMany
    {
        return $this->hasMany(FacturaProveedor::class);
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class);
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

    /** Lo que se le debe: facturas contabilizadas o a medio pagar. */
    public function getSaldoPendienteAttribute(): float
    {
        return (float) $this->facturas()
            ->whereIn('estado', ['contabilizada', 'pagada_parcial'])
            ->selectRaw('COALESCE(SUM(total - total_pagado), 0) as saldo')
            ->value('saldo');
    }

    public function getEtiquetaAttribute(): string
    {
        return $this->codigo.' - '.$this->nombre;
    }
}

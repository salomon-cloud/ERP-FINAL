<?php

declare(strict_types=1);

namespace App\Modules\Inventario\Models;

use App\Models\User;
use App\Modules\Compartido\Traits\TieneBitacora;
use App\Modules\Compartido\Traits\TieneCamposAuditoria;
use App\Modules\Inventario\Enums\EstadoTraspaso;
use App\Modules\Inventario\Observers\ObservadorTraspaso;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Mercancia que pasa de un almacen a otro.
 *
 * Son dos movimientos, no uno: la salida del origen cuando el traspaso se
 * envia, y la entrada al destino cuando se recibe. En medio la mercancia esta
 * "en transito" y no la tiene ninguno de los dos, que es exactamente lo que
 * pasa en la realidad.
 *
 * `numero_traspaso` no es fillable: lo pone el observer con ServicioFolios.
 */
#[ObservedBy([ObservadorTraspaso::class])]
class Traspaso extends Model
{
    use SoftDeletes, TieneBitacora, TieneCamposAuditoria;

    protected $table = 'traspasos';

    protected $fillable = [
        'organizacion_id',
        'almacen_origen_id',
        'almacen_destino_id',
        'solicitado_por',
    ];

    protected $attributes = [
        'estado' => 'borrador',
    ];

    protected $casts = [
        'estado' => EstadoTraspaso::class,
        'aprobado_en' => 'datetime',
        'enviado_en' => 'datetime',
        'recibido_en' => 'datetime',
    ];

    public function almacenOrigen(): BelongsTo
    {
        return $this->belongsTo(Almacen::class, 'almacen_origen_id');
    }

    public function almacenDestino(): BelongsTo
    {
        return $this->belongsTo(Almacen::class, 'almacen_destino_id');
    }

    public function lineas(): HasMany
    {
        return $this->hasMany(TraspasoLinea::class);
    }

    public function solicitadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'solicitado_por');
    }

    public function aprobadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'aprobado_por');
    }

    public function scopeBuscar(Builder $consulta, ?string $termino): Builder
    {
        return blank($termino)
            ? $consulta
            : $consulta->where('numero_traspaso', 'like', '%'.$termino.'%');
    }
}

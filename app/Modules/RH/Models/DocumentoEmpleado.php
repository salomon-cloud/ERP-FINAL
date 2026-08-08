<?php

declare(strict_types=1);

namespace App\Modules\RH\Models;

use App\Modules\Compartido\Traits\TieneBitacora;
use App\Modules\Compartido\Traits\TieneCamposAuditoria;
use App\Modules\RH\Enums\EstadoDocumentoEmpleado;
use App\Modules\RH\Enums\TipoDocumentoEmpleado;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * El expediente digital: el METADATO de RH sobre un documento del empleado
 * (que es, hasta cuando vale, si ya lo entrego).
 *
 * El archivo en si NO vive aqui. Vive en la tabla compartida `adjuntos`, que es
 * de Compartido, y se sube con su AttachmentService. `adjunto_id` se queda como
 * columna sin relacion Eloquent hasta que Compartido publique su modelo Adjunto;
 * RH no declara un modelo propio sobre una tabla ajena.
 *
 * Un documento sin adjunto es uno que el expediente exige y todavia esta
 * pendiente de entrega.
 */
class DocumentoEmpleado extends Model
{
    use SoftDeletes, TieneBitacora, TieneCamposAuditoria;

    protected $table = 'documentos_empleado';

    protected $fillable = [
        'empleado_id',
        'adjunto_id',
        'tipo_documento',
        'titulo',
        'vigencia',
        'estado',
    ];

    /** El mismo valor por omision que declara la migracion. */
    protected $attributes = [
        'estado' => 'vigente',
    ];

    protected $casts = [
        'vigencia' => 'date',
        'tipo_documento' => TipoDocumentoEmpleado::class,
        'estado' => EstadoDocumentoEmpleado::class,
    ];

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class);
    }

    /** Documentos vigentes cuya vigencia cae dentro de los proximos N dias. */
    public function scopePorVencer(Builder $consulta, int $dias = 30): Builder
    {
        return $consulta->where('estado', EstadoDocumentoEmpleado::Vigente)
            ->whereNotNull('vigencia')
            ->whereBetween('vigencia', [now()->toDateString(), now()->addDays($dias)->toDateString()]);
    }
}

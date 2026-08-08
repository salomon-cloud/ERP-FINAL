<?php

declare(strict_types=1);

namespace App\Modules\Compartido\Models;

use Illuminate\Database\Eloquent\Model;

class SecuenciaDocumento extends Model
{
    protected $table = 'secuencias_documento';

    protected $fillable = [
        'modulo',
        'prefijo',
        'sufijo',
        'numero_actual',
        'relleno',
        'activo',
    ];

    protected $casts = [
        'numero_actual' => 'integer',
        'relleno' => 'integer',
        'activo' => 'boolean',
    ];

    /** Convierte un contador en el folio visible: PED-000123. */
    public function formatear(int $numero): string
    {
        return $this->prefijo.str_pad((string) $numero, $this->relleno, '0', STR_PAD_LEFT).$this->sufijo;
    }
}

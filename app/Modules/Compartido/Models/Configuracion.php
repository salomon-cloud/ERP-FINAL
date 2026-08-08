<?php

declare(strict_types=1);

namespace App\Modules\Compartido\Models;

use App\Modules\Compartido\Traits\TieneCamposAuditoria;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Configuracion que un administrador puede cambiar en caliente, direccionada
 * como grupo.clave. Los valores estaticos por omision viven en
 * config/sisen.php y son el respaldo.
 */
class Configuracion extends Model
{
    use SoftDeletes, TieneCamposAuditoria;

    protected $table = 'configuraciones';

    protected $fillable = [
        'grupo',
        'clave',
        'valor',
        'es_json',
        'descripcion',
    ];

    protected $casts = [
        'es_json' => 'boolean',
    ];

    /** El valor guardado, decodificado cuando la fila contiene JSON. */
    public function valorTipado(): mixed
    {
        return $this->es_json ? json_decode((string) $this->valor, true) : $this->valor;
    }
}

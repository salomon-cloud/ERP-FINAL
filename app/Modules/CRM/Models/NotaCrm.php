<?php

declare(strict_types=1);

namespace App\Modules\CRM\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class NotaCrm extends Model
{
    use SoftDeletes;

    protected $table = 'notas_crm';

    protected $fillable = ['entidad_tipo', 'entidad_id', 'autor_id', 'cuerpo', 'fijada'];

    protected $casts = ['fijada' => 'boolean'];

    public function autor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'autor_id');
    }
}
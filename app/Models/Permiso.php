<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Permiso extends Model
{
    protected $fillable = ['empleado_id', 'tipo', 'fecha_inicio', 'fecha_fin', 'motivo', 'estado'];
    protected $casts = ['fecha_inicio' => 'date', 'fecha_fin' => 'date'];

    public function empleado()
    {
        return $this->belongsTo(Empleado::class);
    }
}

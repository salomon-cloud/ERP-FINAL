<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Departamento extends Model
{
    protected $fillable = ['nombre', 'descripcion', 'responsable', 'estado'];

    public function empleados()
    {
        return $this->hasMany(Empleado::class);
    }

    public function puestos()
    {
        return $this->hasMany(Puesto::class);
    }
}

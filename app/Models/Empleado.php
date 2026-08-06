<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Empleado extends Model
{
    protected $fillable = [
        'departamento_id', 'puesto_id', 'user_id', 'nombre', 'apellidos', 'curp', 'rfc', 'correo',
        'telefono', 'direccion', 'fecha_nacimiento', 'fecha_contratacion', 'sueldo_base', 'estado', 'fotografia',
    ];

    protected $casts = [
        'fecha_nacimiento' => 'date',
        'fecha_contratacion' => 'date',
        'sueldo_base' => 'decimal:2',
    ];

    public function departamento()
    {
        return $this->belongsTo(Departamento::class);
    }

    public function puesto()
    {
        return $this->belongsTo(Puesto::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function nominas()
    {
        return $this->hasMany(Nomina::class);
    }

    public function asistencias()
    {
        return $this->hasMany(Asistencia::class);
    }

    public function permisos()
    {
        return $this->hasMany(Permiso::class);
    }

    public function getNombreCompletoAttribute(): string
    {
        return trim($this->nombre . ' ' . $this->apellidos);
    }
}

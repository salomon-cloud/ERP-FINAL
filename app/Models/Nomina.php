<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Nomina extends Model
{
    protected $fillable = [
        'empleado_id', 'periodo_pago', 'fecha_pago', 'sueldo_base', 'bonos', 'horas_extra',
        'deducciones', 'isr', 'imss', 'total_pagar', 'estado',
    ];

    protected $casts = ['fecha_pago' => 'date'];

    public function empleado()
    {
        return $this->belongsTo(Empleado::class);
    }

    public static function calcularTotal(array $data): float
    {
        return (float) ($data['sueldo_base'] ?? 0) + (float) ($data['bonos'] ?? 0) + (float) ($data['horas_extra'] ?? 0)
            - (float) ($data['deducciones'] ?? 0) - (float) ($data['isr'] ?? 0) - (float) ($data['imss'] ?? 0);
    }
}

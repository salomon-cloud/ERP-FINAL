<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Nomina extends Model
{
    protected $fillable = [
        'empleado_id', 'periodo_pago', 'fecha_pago', 'sueldo_base', 'bonos', 'horas_extra',
        'deducciones', 'isr', 'imss', 'total_pagar', 'estado', 'metodo_pago', 'created_by', 'paid_by', 'fecha_pago_real',
    ];

    protected $casts = ['fecha_pago' => 'date', 'fecha_pago_real' => 'datetime'];

    public function empleado()
    {
        return $this->belongsTo(Empleado::class);
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function pagador()
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function getFolioAttribute(): string
    {
        $anio = optional($this->created_at)->year ?? $this->fecha_pago->year;

        return 'SN-'.$anio.'-'.str_pad($this->id, 4, '0', STR_PAD_LEFT);
    }

    public function getMetodoPagoLabelAttribute(): string
    {
        return match ($this->metodo_pago) {
            'transferencia' => 'Transferencia',
            'efectivo' => 'Efectivo',
            'cheque' => 'Cheque',
            default => 'Por definir',
        };
    }

    public static function calcularTotal(array $data): float
    {
        return (float) ($data['sueldo_base'] ?? 0) + (float) ($data['bonos'] ?? 0) + (float) ($data['horas_extra'] ?? 0)
            - (float) ($data['deducciones'] ?? 0) - (float) ($data['isr'] ?? 0) - (float) ($data['imss'] ?? 0);
    }

    public static function sugerirIsr(float $sueldo): float
    {
        foreach (config('sistema.isr_tabla', []) as $tramo) {
            if ($sueldo > (float) $tramo['min'] && $sueldo <= (float) $tramo['max']) {
                return round((float) $tramo['cuota_fija'] + ($sueldo - (float) $tramo['min']) * (float) $tramo['porcentaje'], 2);
            }
        }

        return 0.0;
    }

    public static function sugerirImss(float $sueldo): float
    {
        return round($sueldo * (config('sistema.imss_cuota_obrera') / 100), 2);
    }
}

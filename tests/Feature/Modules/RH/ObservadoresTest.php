<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\RH;

use App\Modules\RH\Models\Asistencia;
use App\Modules\RH\Models\Nomina;
use App\Modules\RH\Models\NominaCorrida;
use App\Modules\RH\Models\NominaPeriodo;
use App\Modules\RH\Models\Permiso;
use Illuminate\Support\Facades\DB;

/**
 * Los datos derivados: los que el usuario nunca captura porque el sistema los
 * calcula, y que por eso son los que se rompen sin que nadie se de cuenta.
 */
class ObservadoresTest extends PruebaRH
{
    public function test_las_horas_trabajadas_salen_de_la_entrada_y_la_salida(): void
    {
        $asistencia = Asistencia::create([
            'empleado_id' => $this->empleado()->id,
            'fecha' => '2026-08-03',
            'hora_entrada' => '09:00:00',
            'hora_salida' => '18:30:00',
            'estado' => 'presente',
        ]);

        $this->assertEquals(9.5, (float) $asistencia->refresh()->horas_trabajadas);

        $asistencia->update(['hora_salida' => '17:00:00']);

        $this->assertEquals(8.0, (float) $asistencia->refresh()->horas_trabajadas);
    }

    public function test_sin_alguna_de_las_horas_no_se_inventan_horas(): void
    {
        $asistencia = Asistencia::create([
            'empleado_id' => $this->empleado()->id,
            'fecha' => '2026-08-04',
            'estado' => 'falta',
        ]);

        $this->assertEquals(0.0, (float) $asistencia->refresh()->horas_trabajadas);
    }

    public function test_los_dias_del_permiso_salen_del_rango_e_incluyen_los_dos_extremos(): void
    {
        $permiso = Permiso::create([
            'empleado_id' => $this->empleado()->id, 'tipo' => 'vacaciones',
            'fecha_inicio' => '2026-08-10', 'fecha_fin' => '2026-08-14',
            'motivo' => 'Vacaciones',
        ]);

        $this->assertEquals(5.0, (float) $permiso->refresh()->dias);
    }

    public function test_un_medio_dia_explicito_se_respeta(): void
    {
        $permiso = Permiso::create([
            'empleado_id' => $this->empleado()->id, 'tipo' => 'permiso',
            'fecha_inicio' => '2026-08-10', 'fecha_fin' => '2026-08-10',
            'dias' => 0.5, 'motivo' => 'Medio dia',
        ]);

        $this->assertEquals(0.5, (float) $permiso->refresh()->dias);
    }

    public function test_el_total_del_recibo_se_deriva_y_no_se_captura(): void
    {
        $recibo = Nomina::create([
            'empleado_id' => $this->empleado()->id,
            'periodo_pago' => '2026-Q15', 'fecha_pago' => '2026-08-16',
            'sueldo_base' => 7500, 'bonos' => 500, 'horas_extra' => 300,
            'deducciones' => 100, 'isr' => 800, 'imss' => 200,
            'total_pagar' => 999999,      // se ignora
            'estado' => 'pendiente',
        ]);

        $this->assertEquals(7200.0, (float) $recibo->refresh()->total_pagar);
    }

    public function test_la_corrida_recibe_folio_de_la_secuencia(): void
    {
        $primera = NominaCorrida::create(['periodo_id' => $this->periodo()->id]);
        $segunda = NominaCorrida::create(['periodo_id' => $this->periodo()->id]);

        $this->assertSame('NOM-000001', $primera->numero_corrida);
        $this->assertSame('NOM-000002', $segunda->numero_corrida);
    }

    public function test_los_totales_de_la_corrida_siguen_a_sus_recibos(): void
    {
        $corrida = NominaCorrida::create(['periodo_id' => $this->periodo()->id]);

        $recibo = Nomina::create([
            'empleado_id' => $this->empleado()->id, 'corrida_id' => $corrida->id,
            'periodo_pago' => '2026-Q15', 'fecha_pago' => '2026-08-16',
            'sueldo_base' => 10000, 'isr' => 1000, 'estado' => 'pendiente',
        ]);

        $totales = fn () => DB::table('nomina_corridas')->where('id', $corrida->id)->first();

        $this->assertSame(1, (int) $totales()->total_empleados);
        $this->assertEquals(9000.0, (float) $totales()->total_neto);

        $recibo->delete();

        $this->assertSame(0, (int) $totales()->total_empleados);
        $this->assertEquals(0.0, (float) $totales()->total_neto);
    }

    public function test_sincronizar_totales_no_ensucia_la_bitacora(): void
    {
        // Generar cien recibos no puede dejar cien renglones de "corrida
        // actualizada": por eso los totales se escriben sin disparar eventos.
        $corrida = NominaCorrida::create(['periodo_id' => $this->periodo()->id]);

        $antes = DB::table('bitacora_auditoria')
            ->where('entidad_tipo', NominaCorrida::class)->where('entidad_id', $corrida->id)->count();

        Nomina::create([
            'empleado_id' => $this->empleado()->id, 'corrida_id' => $corrida->id,
            'periodo_pago' => '2026-Q15', 'fecha_pago' => '2026-08-16',
            'sueldo_base' => 5000, 'estado' => 'pendiente',
        ]);

        $despues = DB::table('bitacora_auditoria')
            ->where('entidad_tipo', NominaCorrida::class)->where('entidad_id', $corrida->id)->count();

        $this->assertSame($antes, $despues);
    }

    private function periodo(): NominaPeriodo
    {
        return NominaPeriodo::create([
            'codigo_periodo' => 'P-'.fake()->unique()->numerify('####'),
            'fecha_inicio' => '2026-08-01', 'fecha_fin' => '2026-08-15',
            'fecha_pago' => '2026-08-16', 'frecuencia' => 'quincenal', 'estado' => 'abierto',
        ]);
    }
}

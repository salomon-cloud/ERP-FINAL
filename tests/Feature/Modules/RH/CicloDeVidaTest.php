<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\RH;

use App\Modules\RH\Enums\EstadoAsistencia;
use App\Modules\RH\Enums\EstadoNominaCorrida;
use App\Modules\RH\Enums\EstadoPermiso;
use App\Modules\RH\Models\Asistencia;
use App\Modules\RH\Models\NominaCorrida;
use App\Modules\RH\Models\NominaPeriodo;
use App\Modules\RH\Models\Permiso;
use App\Modules\RH\Services\ServicioAprobacionPermisos;
use App\Modules\RH\Services\ServicioCorridaNomina;
use RuntimeException;

/**
 * El ciclo de vida de los dos documentos del modulo: la solicitud de permiso y
 * la corrida de nomina.
 *
 * Es la parte que no puede romperse en silencio: aqui se decide a quien se le
 * paga y cuanto.
 */
class CicloDeVidaTest extends PruebaRH
{
    public function test_aprobar_un_permiso_marca_los_dias_en_asistencia(): void
    {
        $empleado = $this->empleado();

        $permiso = Permiso::create([
            'empleado_id' => $empleado->id, 'tipo' => 'vacaciones',
            'fecha_inicio' => '2026-08-10', 'fecha_fin' => '2026-08-12',
            'motivo' => 'Vacaciones',
        ]);

        $this->actingAs($this->admin)
            ->patch('/rh/permisos/'.$permiso->id.'/revisar', [
                'estado' => 'aprobado',
                'comentario_revision' => 'Autorizado',
            ])
            ->assertRedirect(route('rh.permisos.index'));

        $permiso->refresh();

        $this->assertSame(EstadoPermiso::Aprobado, $permiso->estado);
        $this->assertSame($this->admin->id, $permiso->revisado_por);
        $this->assertSame(3, Asistencia::where('empleado_id', $empleado->id)
            ->where('estado', EstadoAsistencia::Permiso)->count());
    }

    public function test_una_solicitud_ya_revisada_no_se_vuelve_a_decidir(): void
    {
        $permiso = Permiso::create([
            'empleado_id' => $this->empleado()->id, 'tipo' => 'permiso',
            'fecha_inicio' => '2026-08-10', 'fecha_fin' => '2026-08-10',
            'motivo' => 'Tramite', 'estado' => EstadoPermiso::Aprobado,
        ]);

        $this->actingAs($this->admin)
            ->patch('/rh/permisos/'.$permiso->id.'/revisar', ['estado' => 'rechazado'])
            ->assertSessionHas('error');

        $this->assertSame(EstadoPermiso::Aprobado, $permiso->refresh()->estado);
    }

    public function test_la_decision_solo_puede_ser_aprobar_o_rechazar(): void
    {
        $permiso = Permiso::create([
            'empleado_id' => $this->empleado()->id, 'tipo' => 'permiso',
            'fecha_inicio' => '2026-08-10', 'fecha_fin' => '2026-08-10', 'motivo' => 'Tramite',
        ]);

        $this->actingAs($this->admin)
            ->patch('/rh/permisos/'.$permiso->id.'/revisar', ['estado' => 'pendiente'])
            ->assertSessionHasErrors('estado');
    }

    public function test_la_corrida_recorre_su_ciclo_completo(): void
    {
        $this->actingAs($this->admin);

        $periodo = $this->periodoQuincenal();
        $this->empleado(['sueldo_base' => 30000, 'frecuencia_pago' => 'quincenal']);

        // Crear
        $this->post('/rh/nomina-corridas', ['periodo_id' => $periodo->id]);
        $corrida = NominaCorrida::firstOrFail();

        $this->assertSame(EstadoNominaCorrida::Borrador, $corrida->estado);
        $this->assertStringStartsWith('NOM-', $corrida->numero_corrida);

        // Procesar
        $this->post('/rh/nomina-corridas/'.$corrida->id.'/procesar')->assertRedirect();
        $corrida->refresh();

        $this->assertSame(EstadoNominaCorrida::Procesada, $corrida->estado);
        $this->assertSame(1, $corrida->total_empleados);
        // 30000 mensuales / 24 quincenas al ano * 12 meses = 15000
        $this->assertEquals(15000.0, (float) $corrida->recibos()->first()->sueldo_base);

        // Aplicar
        $this->post('/rh/nomina-corridas/'.$corrida->id.'/aplicar', [
            'version_fila' => $corrida->version_fila,
        ])->assertRedirect();

        $this->assertSame(EstadoNominaCorrida::Aplicada, $corrida->refresh()->estado);
    }

    public function test_aplicar_con_una_version_vieja_se_rechaza(): void
    {
        $this->actingAs($this->admin);

        $corrida = $this->corridaProcesada();

        $this->post('/rh/nomina-corridas/'.$corrida->id.'/aplicar', ['version_fila' => 99])
            ->assertSessionHas('error');

        $this->assertSame(EstadoNominaCorrida::Procesada, $corrida->refresh()->estado);
    }

    public function test_una_corrida_aplicada_ya_no_se_cancela(): void
    {
        $this->actingAs($this->admin);

        $corrida = $this->corridaProcesada();
        app(ServicioCorridaNomina::class)->aplicar($corrida, $this->admin, $corrida->version_fila);

        $this->delete('/rh/nomina-corridas/'.$corrida->id)->assertSessionHas('error');

        $this->assertSame(EstadoNominaCorrida::Aplicada, $corrida->refresh()->estado);
    }

    public function test_un_periodo_cerrado_no_admite_corridas(): void
    {
        $periodo = $this->periodoQuincenal(['estado' => 'cerrado']);

        $this->expectException(RuntimeException::class);

        app(ServicioCorridaNomina::class)->crear($periodo);
    }

    public function test_los_dias_sin_goce_se_descuentan_del_recibo(): void
    {
        $this->actingAs($this->admin);

        $periodo = $this->periodoQuincenal();
        $empleado = $this->empleado(['sueldo_base' => 30000, 'frecuencia_pago' => 'quincenal']);

        $permiso = Permiso::create([
            'empleado_id' => $empleado->id, 'tipo' => 'permiso',
            'fecha_inicio' => '2026-08-04', 'fecha_fin' => '2026-08-05',
            'con_goce' => false, 'motivo' => 'Asunto personal',
        ]);

        app(ServicioAprobacionPermisos::class)
            ->aprobar($permiso, $this->admin);

        $servicio = app(ServicioCorridaNomina::class);
        $corrida = $servicio->crear($periodo);
        $servicio->procesar($corrida, $this->admin);

        $recibo = $corrida->recibos()->first();

        // 15000 de quincena / 15 dias * 2 dias = 2000
        $this->assertEquals(2.0, (float) $recibo->dias_ausencia);
        $this->assertEquals(2000.0, (float) $recibo->deducciones);
        $this->assertEquals(13000.0, (float) $recibo->total_pagar);
    }

    public function test_solo_entran_los_empleados_de_la_misma_frecuencia(): void
    {
        $this->actingAs($this->admin);

        $periodo = $this->periodoQuincenal();
        $this->empleado(['frecuencia_pago' => 'quincenal']);
        $this->empleado(['frecuencia_pago' => 'mensual']);

        $servicio = app(ServicioCorridaNomina::class);
        $corrida = $servicio->crear($periodo);
        $servicio->procesar($corrida, $this->admin);

        $this->assertSame(1, $corrida->refresh()->total_empleados);
    }

    private function periodoQuincenal(array $atributos = []): NominaPeriodo
    {
        return NominaPeriodo::create(array_merge([
            'codigo_periodo' => 'P-'.fake()->unique()->numerify('####'),
            'fecha_inicio' => '2026-08-01',
            'fecha_fin' => '2026-08-15',
            'fecha_pago' => '2026-08-16',
            'frecuencia' => 'quincenal',
            'estado' => 'abierto',
        ], $atributos));
    }

    private function corridaProcesada(): NominaCorrida
    {
        $periodo = $this->periodoQuincenal();
        $this->empleado(['frecuencia_pago' => 'quincenal']);

        $servicio = app(ServicioCorridaNomina::class);
        $corrida = $servicio->crear($periodo);

        return $servicio->procesar($corrida, $this->admin);
    }
}

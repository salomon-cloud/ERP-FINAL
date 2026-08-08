<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\RH;

use App\Modules\Compartido\Support\RegistroMenu;
use App\Modules\RH\Models\Contrato;
use App\Modules\RH\Models\DocumentoEmpleado;
use App\Modules\RH\Models\EvaluacionDesempeno;
use App\Modules\RH\Models\NominaPeriodo;
use App\Modules\RH\Models\Permiso;

/**
 * Que todas las pantallas del modulo se pinten, y que el menu lateral no invada
 * la banda del modulo vecino.
 */
class PantallasTest extends PruebaRH
{
    public function test_todas_las_listas_responden(): void
    {
        $this->actingAs($this->admin);

        foreach ([
            '/rh', '/rh/empleados', '/rh/departamentos', '/rh/puestos', '/rh/organigrama',
            '/rh/asistencias', '/rh/permisos', '/rh/nomina-periodos', '/rh/nomina-corridas',
            '/rh/nominas', '/rh/contratos', '/rh/documentos', '/rh/evaluaciones', '/rh/reportes',
        ] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_todos_los_formularios_de_alta_responden(): void
    {
        $this->actingAs($this->admin);
        $this->departamento();   // para que los selects traigan opciones

        foreach ([
            '/rh/empleados/create', '/rh/departamentos/create', '/rh/puestos/create',
            '/rh/asistencias/create', '/rh/permisos/create', '/rh/nomina-periodos/create',
            '/rh/nomina-corridas/create', '/rh/nominas/create', '/rh/contratos/create',
            '/rh/documentos/create', '/rh/evaluaciones/create',
        ] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_las_pantallas_de_detalle_responden(): void
    {
        $this->actingAs($this->admin);

        $empleado = $this->empleado();

        $permiso = Permiso::create([
            'empleado_id' => $empleado->id, 'tipo' => 'permiso',
            'fecha_inicio' => '2026-08-10', 'fecha_fin' => '2026-08-10', 'motivo' => 'Tramite',
        ]);

        $contrato = Contrato::create([
            'empleado_id' => $empleado->id, 'tipo_contrato' => 'indefinido',
            'fecha_inicio' => '2024-01-01', 'sueldo' => 30000, 'jornada_horas' => 48,
            'estado' => 'vigente',
        ]);

        $evaluacion = EvaluacionDesempeno::create([
            'empleado_id' => $empleado->id, 'periodo_evaluado' => '2026-S1',
            'calificacion' => 90, 'estado' => 'enviada',
        ]);

        DocumentoEmpleado::create([
            'empleado_id' => $empleado->id, 'tipo_documento' => 'identificacion',
            'titulo' => 'INE', 'estado' => 'vigente',
        ]);

        $periodo = NominaPeriodo::create([
            'codigo_periodo' => 'P-0001', 'fecha_inicio' => '2026-08-01', 'fecha_fin' => '2026-08-15',
            'fecha_pago' => '2026-08-16', 'frecuencia' => 'quincenal', 'estado' => 'abierto',
        ]);

        $this->get('/rh/empleados/'.$empleado->id)->assertOk()->assertSee('INE');
        $this->get('/rh/empleados/'.$empleado->id.'/edit')->assertOk();
        $this->get('/rh/permisos/'.$permiso->id)->assertOk()->assertSee('Aprobar');
        $this->get('/rh/contratos/'.$contrato->id)->assertOk();
        $this->get('/rh/evaluaciones/'.$evaluacion->id)->assertOk();
        $this->get('/rh/nomina-periodos/'.$periodo->id)->assertOk();
    }

    public function test_los_reportes_responden_y_exportan_csv(): void
    {
        $this->actingAs($this->admin);
        $this->empleado();

        foreach ([
            'empleados', 'asistencias', 'permisos', 'nomina',
            'contratos-por-vencer', 'plantilla-por-departamento',
        ] as $reporte) {
            $this->get("/rh/reportes/{$reporte}")->assertOk();

            $csv = $this->get("/rh/reportes/{$reporte}?formato=csv");
            $csv->assertOk();
            $csv->assertHeader('content-type', 'text/csv; charset=UTF-8');
        }
    }

    public function test_el_csv_trae_los_datos_y_abre_bien_en_excel(): void
    {
        $this->actingAs($this->admin);
        $empleado = $this->empleado(['nombre' => 'Ruben', 'apellidos' => 'Nunez']);

        $contenido = $this->get('/rh/reportes/empleados?formato=csv')->streamedContent();

        $this->assertStringStartsWith("\xEF\xBB\xBF", $contenido, 'Falta el BOM que Excel necesita.');
        $this->assertStringContainsString('Ruben Nunez', $contenido);
        $this->assertStringContainsString('Numero,Nombre,Departamento', $contenido);
    }

    public function test_el_menu_de_rh_no_invade_la_banda_del_modulo_vecino(): void
    {
        $entradas = collect(RegistroMenu::visiblesPara($this->admin));

        $rh = $entradas->where('modulo', 'RH');
        $crm = $entradas->firstWhere('modulo', 'CRM');

        $this->assertGreaterThanOrEqual(13, $rh->count());

        // CRM vale 60. Si una entrada de RH se pasa de ahi, CRM aparece
        // sandwicheado en medio de la lista de RH, que fue justo lo que paso.
        $this->assertLessThan(
            $crm['orden'],
            $rh->max('orden'),
            'Alguna entrada de RH se paso a la banda del siguiente modulo.'
        );
    }

    public function test_el_tablero_pinta_kpis_reales_ligados_a_su_lista(): void
    {
        $this->actingAs($this->admin);
        $this->empleado();

        $this->get('/rh')
            ->assertOk()
            ->assertSee('Empleados activos')
            ->assertSee('Contratos por vencer')
            ->assertSee('/rh/empleados?estado=activo', false);
    }
}

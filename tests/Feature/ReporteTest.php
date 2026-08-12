<?php

namespace Tests\Feature;

use App\Models\Departamento;
use App\Models\Empleado;
use App\Models\Nomina;
use App\Models\Puesto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReporteTest extends TestCase
{
    use RefreshDatabase;

    private function setupContador(): User
    {
        return User::create(['name' => 'Contador', 'email' => 'c@test.com', 'password' => 'password', 'role' => 'Contador', 'estado' => 'activo']);
    }

    private function setupDatos(): Empleado
    {
        $dep = Departamento::create(['nombre' => 'Finanzas', 'responsable' => 'X', 'estado' => 'activo']);
        $puesto = Puesto::create(['departamento_id' => $dep->id, 'nombre' => 'Contador', 'estado' => 'activo']);
        $empleado = Empleado::create([
            'departamento_id' => $dep->id, 'puesto_id' => $puesto->id, 'nombre' => 'Test', 'apellidos' => 'Demo',
            'curp' => 'TEDM000101HDFRRP01', 'rfc' => 'TEDM000101A1', 'correo' => 't@test.com',
            'sueldo_base' => 30000, 'estado' => 'activo', 'fecha_contratacion' => '2024-01-01', 'fecha_nacimiento' => '1990-01-01',
        ]);

        Nomina::create([
            'empleado_id' => $empleado->id, 'periodo_pago' => 'Q1 2026', 'fecha_pago' => '2026-05-15',
            'sueldo_base' => 15000, 'bonos' => 1000, 'total_pagar' => 16000, 'estado' => 'pagada', 'metodo_pago' => 'transferencia',
        ]);
        Nomina::create([
            'empleado_id' => $empleado->id, 'periodo_pago' => 'Q2 2026', 'fecha_pago' => '2026-06-15',
            'sueldo_base' => 15000, 'total_pagar' => 15000, 'estado' => 'pendiente',
        ]);

        return $empleado;
    }

    public function test_reporte_avanzando_renderiza(): void
    {
        $this->setupDatos();
        $contador = $this->setupContador();

        $this->actingAs($contador)->get('/reportes/resumen-periodo')->assertOk()->assertSee('05-2026')->assertSee('06-2026');
        $this->actingAs($contador)->get('/reportes/costo-departamento')->assertOk()->assertSee('Finanzas');
        $this->actingAs($contador)->get('/reportes/comparativo?anio=2026')->assertOk()->assertSee('05-2026');
    }

    public function test_resumen_periodo_respeta_filtro_periodo(): void
    {
        $this->setupDatos();
        $contador = $this->setupContador();

        $this->actingAs($contador)->get('/reportes/resumen-periodo?periodo=2026-05')
            ->assertOk()->assertSee('05-2026')->assertDontSee('06-2026');
    }

    public function test_export_csv_nominas(): void
    {
        $this->setupDatos();
        $contador = $this->setupContador();

        $response = $this->actingAs($contador)->get('/reportes/nominas?exportar=1');
        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString("\xEF\xBB\xBF", $response->getContent());
        $this->assertStringContainsString('Test Demo', $response->getContent());
    }

    public function test_export_csv_pagos_pendientes(): void
    {
        $this->setupDatos();
        $contador = $this->setupContador();

        $response = $this->actingAs($contador)->get('/reportes/pagos-pendientes?exportar=1');
        $response->assertOk();
        $this->assertStringContainsString('Q2 2026', $response->getContent());
        $this->assertStringNotContainsString('Q1 2026', $response->getContent());
    }

    public function test_export_csv_resumen_y_costo(): void
    {
        $this->setupDatos();
        $contador = $this->setupContador();

        $resumen = $this->actingAs($contador)->get('/reportes/resumen-periodo?exportar=1');
        $resumen->assertOk();
        $this->assertStringContainsString('05-2026', $resumen->getContent());

        $costo = $this->actingAs($contador)->get('/reportes/costo-departamento?exportar=1');
        $costo->assertOk();
        $this->assertStringContainsString('Finanzas', $costo->getContent());

        $comp = $this->actingAs($contador)->get('/reportes/comparativo?anio=2026&exportar=1');
        $comp->assertOk();
        $this->assertStringContainsString('05-2026', $comp->getContent());
    }
}

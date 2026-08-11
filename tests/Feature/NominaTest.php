<?php

namespace Tests\Feature;

use App\Models\Departamento;
use App\Models\Empleado;
use App\Models\Nomina;
use App\Models\Puesto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NominaTest extends TestCase
{
    use RefreshDatabase;

    private function setupData(): array
    {
        $dep = Departamento::create(['nombre' => 'Finanzas', 'responsable' => 'X', 'estado' => 'activo']);
        $puesto = Puesto::create(['departamento_id' => $dep->id, 'nombre' => 'Contador', 'estado' => 'activo']);
        $empleado = Empleado::create([
            'departamento_id' => $dep->id, 'puesto_id' => $puesto->id, 'nombre' => 'Test', 'apellidos' => 'Demo',
            'curp' => 'TEDM000101HDFRRP01', 'rfc' => 'TEDM000101A1', 'correo' => 't@test.com',
            'sueldo_base' => 30000, 'estado' => 'activo', 'fecha_contratacion' => '2024-01-01', 'fecha_nacimiento' => '1990-01-01',
        ]);
        $contador = User::create(['name' => 'Contador', 'email' => 'c@test.com', 'password' => 'password', 'role' => 'Contador', 'estado' => 'activo']);

        return [$empleado, $contador];
    }

    public function test_store_registra_created_by_y_metodo_pago(): void
    {
        [$empleado, $contador] = $this->setupData();
        $this->actingAs($contador)->post('/nominas', [
            'empleado_id' => $empleado->id, 'periodo_pago' => 'Q1 2026', 'fecha_pago' => '2026-05-15',
            'sueldo_base' => 15000, 'bonos' => 1000, 'horas_extra' => 0, 'deducciones' => 500, 'isr' => 1000, 'imss' => 400,
            'estado' => 'pendiente', 'metodo_pago' => 'transferencia',
        ])->assertRedirect(route('nominas.index'));

        $nomina = Nomina::first();
        $this->assertDatabaseHas('nominas', [
            'id' => $nomina->id, 'total_pagar' => 14100, 'created_by' => $contador->id, 'metodo_pago' => 'transferencia',
        ]);
    }

    public function test_total_negativo_es_invalidado(): void
    {
        [$empleado, $contador] = $this->setupData();
        $this->actingAs($contador)->from('/nominas/create')->post('/nominas', [
            'empleado_id' => $empleado->id, 'periodo_pago' => 'Q1 2026', 'fecha_pago' => '2026-05-15',
            'sueldo_base' => 1000, 'bonos' => 0, 'horas_extra' => 0, 'deducciones' => 2000, 'isr' => 500, 'imss' => 0,
            'estado' => 'pendiente',
        ])->assertSessionHasErrors('total_pagar');
        $this->assertDatabaseCount('nominas', 0);
    }

    public function test_duplicado_empleado_y_periodo_es_invalidado(): void
    {
        [$empleado, $contador] = $this->setupData();
        $payload = [
            'empleado_id' => $empleado->id, 'periodo_pago' => 'Q1 2026', 'fecha_pago' => '2026-05-15',
            'sueldo_base' => 15000, 'bonos' => 0, 'horas_extra' => 0, 'deducciones' => 0, 'isr' => 0, 'imss' => 0,
            'estado' => 'pendiente',
        ];
        $this->actingAs($contador)->post('/nominas', $payload)->assertRedirect();
        $this->actingAs($contador)->post('/nominas', $payload)->assertSessionHasErrors('periodo_pago');
        $this->assertDatabaseCount('nominas', 1);
    }

    public function test_mark_paid_solo_desde_pendiente_y_guarda_auditoria(): void
    {
        [$empleado, $contador] = $this->setupData();
        $nomina = Nomina::create([
            'empleado_id' => $empleado->id, 'periodo_pago' => 'Q1', 'fecha_pago' => '2026-05-15',
            'sueldo_base' => 15000, 'total_pagar' => 15000, 'estado' => 'pendiente', 'created_by' => $contador->id,
        ]);
        $this->actingAs($contador)->patch("/nominas/{$nomina->id}/pagar");

        $this->assertDatabaseHas('nominas', [
            'id' => $nomina->id, 'estado' => 'pagada', 'paid_by' => $contador->id,
        ]);
        $nomina->refresh();
        $this->assertNotNull($nomina->fecha_pago_real);
    }

    public function test_no_se_puede_editar_ni_eliminar_pagada(): void
    {
        [$empleado, $contador] = $this->setupData();
        $nomina = Nomina::create([
            'empleado_id' => $empleado->id, 'periodo_pago' => 'Q1', 'fecha_pago' => '2026-05-15',
            'sueldo_base' => 15000, 'total_pagar' => 15000, 'estado' => 'pagada', 'created_by' => $contador->id,
        ]);

        $this->actingAs($contador)->get("/nominas/{$nomina->id}/edit")->assertStatus(422);
        $this->actingAs($contador)->delete("/nominas/{$nomina->id}")->assertStatus(422);
        $this->assertDatabaseHas('nominas', ['id' => $nomina->id]);
    }

    public function test_recibo_muestra_metodo_pago_y_auditoria(): void
    {
        [$empleado, $admin] = $this->setupData();
        $user = User::create(['name' => 'Admin', 'email' => 'a@test.com', 'password' => 'password', 'role' => 'Administrador', 'estado' => 'activo']);
        $nomina = Nomina::create([
            'empleado_id' => $empleado->id, 'periodo_pago' => 'Q1', 'fecha_pago' => '2026-05-15',
            'sueldo_base' => 15000, 'total_pagar' => 15000, 'estado' => 'pagada',
            'created_by' => $admin->id, 'paid_by' => $user->id, 'fecha_pago_real' => now(), 'metodo_pago' => 'cheque',
        ]);

        $this->actingAs($admin)->get("/nominas/{$nomina->id}")
            ->assertOk()
            ->assertSee(config('sistema.razon_social'))
            ->assertSee($nomina->folio)
            ->assertSee('Cheque')
            ->assertSee('Pagada por');
    }

    public function test_calcula_isr_imss_automaticamente_si_vienen_en_cero(): void
    {
        [$empleado, $contador] = $this->setupData();
        $this->actingAs($contador)->post('/nominas', [
            'empleado_id' => $empleado->id, 'periodo_pago' => 'Q1 2026', 'fecha_pago' => '2026-05-15',
            'sueldo_base' => 15000, 'bonos' => 0, 'horas_extra' => 0, 'deducciones' => 0, 'isr' => 0, 'imss' => 0,
            'estado' => 'pendiente',
        ])->assertRedirect();

        $nomina = Nomina::first();
        $this->assertEquals(Nomina::sugerirIsr(15000), (float) $nomina->isr);
        $this->assertEquals(Nomina::sugerirImss(15000), (float) $nomina->imss);
        $this->assertEqualsWithDelta(Nomina::calcularTotal($nomina->getAttributes()), (float) $nomina->total_pagar, 0.01);
    }

    public function test_sugerir_isr_responde_a_tramos(): void
    {
        $this->assertEqualsWithDelta(1.92, Nomina::sugerirIsr(100), 0.01);
        $this->assertGreaterThan(0.0, Nomina::sugerirIsr(15000));
        $this->assertGreaterThan(Nomina::sugerirIsr(15000), Nomina::sugerirIsr(30000));
    }

    public function test_empleado_no_puede_crear_y_solo_ve_sus_nominas(): void
    {
        [$empleado, $contador] = $this->setupData();
        $empleado2 = Empleado::create([
            'departamento_id' => $empleado->departamento_id, 'puesto_id' => $empleado->puesto_id, 'nombre' => 'Otro', 'apellidos' => 'Usuario',
            'curp' => 'OTUU000102HDFRRP02', 'rfc' => 'OTUU000102B2', 'correo' => 'o@test.com',
            'sueldo_base' => 20000, 'estado' => 'activo', 'fecha_contratacion' => '2024-01-01', 'fecha_nacimiento' => '1991-01-01',
        ]);
        $empleadoUser = User::create(['name' => 'Empleado', 'email' => 'e@test.com', 'password' => 'password', 'role' => 'Empleado', 'estado' => 'activo', 'empleado_id' => $empleado->id]);

        Nomina::create(['empleado_id' => $empleado->id, 'periodo_pago' => 'Q1', 'fecha_pago' => '2026-05-15', 'sueldo_base' => 15000, 'total_pagar' => 15000, 'estado' => 'pendiente']);
        Nomina::create(['empleado_id' => $empleado2->id, 'periodo_pago' => 'Q2', 'fecha_pago' => '2026-05-31', 'sueldo_base' => 20000, 'total_pagar' => 20000, 'estado' => 'pendiente']);

        $this->actingAs($empleadoUser)->post('/nominas')->assertStatus(403);
        $this->actingAs($empleadoUser)->get('/nominas')->assertOk()->assertSee('Q1')->assertDontSee('Q2');
    }

    public function test_index_filtra_por_estado_y_rango(): void
    {
        [$empleado, $contador] = $this->setupData();
        Nomina::create(['empleado_id' => $empleado->id, 'periodo_pago' => 'Q1', 'fecha_pago' => '2026-05-15', 'sueldo_base' => 15000, 'total_pagar' => 15000, 'estado' => 'pagada']);
        Nomina::create(['empleado_id' => $empleado->id, 'periodo_pago' => 'Q2', 'fecha_pago' => '2026-06-15', 'sueldo_base' => 16000, 'total_pagar' => 16000, 'estado' => 'pendiente']);

        $this->actingAs($contador)->get('/nominas?estado=pendiente')
            ->assertOk()->assertSee('Q2')->assertDontSee('Q1');
        $this->actingAs($contador)->get('/nominas?desde=2026-06-01&hasta=2026-06-30')
            ->assertOk()->assertSee('Q2')->assertDontSee('Q1');
    }
}

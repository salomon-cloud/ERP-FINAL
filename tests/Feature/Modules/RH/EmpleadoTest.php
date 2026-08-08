<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\RH;

use App\Modules\RH\Models\Empleado;

class EmpleadoTest extends PruebaRH
{
    public function test_la_lista_busca_y_filtra(): void
    {
        $departamento = $this->departamento(['nombre' => 'Sistemas']);
        $este = $this->empleado(['nombre' => 'Zoraida', 'departamento_id' => $departamento->id]);
        $otro = $this->empleado(['nombre' => 'Baltazar']);

        $this->actingAs($this->admin)
            ->get('/rh/empleados?buscar=Zoraida')
            ->assertOk()
            ->assertSee('Zoraida')
            ->assertDontSee('Baltazar');

        $this->actingAs($this->admin)
            ->get('/rh/empleados?departamento_id='.$departamento->id)
            ->assertOk()
            ->assertSee($este->nombre)
            ->assertDontSee($otro->nombre);
    }

    public function test_se_da_de_alta_un_empleado(): void
    {
        $departamento = $this->departamento();
        $puesto = $this->puesto($departamento);

        $this->actingAs($this->admin)
            ->post('/rh/empleados', [
                'departamento_id' => $departamento->id,
                'puesto_id' => $puesto->id,
                'nombre' => 'Nueva', 'apellidos' => 'Empleada',
                'curp' => 'AAAA900101MDFXXX01', 'rfc' => 'AAAA900101XX1',
                'correo' => 'nueva@pruebas.test',
                'fecha_nacimiento' => '1990-01-01', 'fecha_contratacion' => '2025-01-01',
                'tipo_contrato' => 'indefinido', 'sueldo_base' => 25000,
                'moneda' => 'MXN', 'frecuencia_pago' => 'quincenal', 'estado' => 'activo',
            ])
            ->assertRedirect(route('rh.empleados.index'));

        $this->assertDatabaseHas('empleados', ['correo' => 'nueva@pruebas.test']);
    }

    public function test_no_se_repite_el_rfc(): void
    {
        $existente = $this->empleado();
        $departamento = $this->departamento();

        $this->actingAs($this->admin)
            ->post('/rh/empleados', [
                'departamento_id' => $departamento->id,
                'puesto_id' => $this->puesto($departamento)->id,
                'nombre' => 'Copia', 'apellidos' => 'Duplicada',
                'curp' => 'BBBB900101MDFXXX02', 'rfc' => $existente->rfc,
                'correo' => 'copia@pruebas.test',
                'fecha_nacimiento' => '1990-01-01', 'fecha_contratacion' => '2025-01-01',
                'tipo_contrato' => 'indefinido', 'sueldo_base' => 25000,
                'moneda' => 'MXN', 'frecuencia_pago' => 'quincenal', 'estado' => 'activo',
            ])
            ->assertSessionHasErrors('rfc');
    }

    public function test_nadie_puede_ser_su_propio_jefe(): void
    {
        $empleado = $this->empleado();

        $this->actingAs($this->admin)
            ->put('/rh/empleados/'.$empleado->id, [
                'departamento_id' => $empleado->departamento_id,
                'puesto_id' => $empleado->puesto_id,
                'jefe_id' => $empleado->id,
                'nombre' => $empleado->nombre, 'apellidos' => $empleado->apellidos,
                'curp' => $empleado->curp, 'rfc' => $empleado->rfc, 'correo' => $empleado->correo,
                'fecha_nacimiento' => '1995-01-01', 'fecha_contratacion' => '2024-01-01',
                'tipo_contrato' => 'indefinido', 'sueldo_base' => 30000,
                'moneda' => 'MXN', 'frecuencia_pago' => 'quincenal', 'estado' => 'activo',
            ])
            ->assertSessionHasErrors('jefe_id');
    }

    public function test_el_borrado_es_logico(): void
    {
        $empleado = $this->empleado();

        $this->actingAs($this->admin)
            ->delete('/rh/empleados/'.$empleado->id)
            ->assertRedirect(route('rh.empleados.index'));

        $this->assertSoftDeleted('empleados', ['id' => $empleado->id]);
    }

    public function test_los_mensajes_de_validacion_estan_en_espanol(): void
    {
        // No hay lang/ en la raiz; los textos salen de RequestBase::messages().
        $respuesta = $this->actingAs($this->admin)
            ->from('/rh/empleados/create')
            ->post('/rh/empleados', []);

        $errores = session('errors')->getBag('default');

        $this->assertStringContainsString('obligatorio', $errores->first('nombre'));
        $this->assertStringNotContainsString('validation.', $errores->first('nombre'));
    }

    public function test_la_auditoria_y_la_bitacora_se_llenan_solas(): void
    {
        $this->actingAs($this->admin);

        $empleado = $this->empleado();

        $this->assertSame($this->admin->id, $empleado->creado_por);
        $this->assertDatabaseHas('bitacora_auditoria', [
            'modulo' => 'RH',
            'accion' => 'creado',
            'entidad_tipo' => Empleado::class,
            'entidad_id' => $empleado->id,
        ]);
    }
}

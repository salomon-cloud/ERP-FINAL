<?php

declare(strict_types=1);

use App\Modules\Compartido\Support\EsquemaErp;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * empresas, prospectos, contactos, oportunidades, actividades, notas y tareas.
 *
 * `empresas` son las cuentas B2B a las que se les vende; no confundir con
 * `organizaciones`, que es la empresa que opera el ERP.
 *
 * Un prospecto convertido apunta al cliente de Ventas que produjo; CRM nunca
 * crea su propia copia de un cliente.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('empresas', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->foreignId('organizacion_id')->nullable()->constrained('organizaciones')->nullOnDelete();
            $tabla->string('nombre', 200);
            $tabla->string('rfc', 30)->nullable();
            $tabla->string('giro', 100)->nullable();
            $tabla->string('sitio_web', 200)->nullable();
            $tabla->string('correo', 150)->nullable();
            $tabla->string('telefono', 30)->nullable();
            $tabla->text('direccion')->nullable();
            $tabla->string('estado', 20)->default('activo');
            EsquemaErp::auditoria($tabla);

            $tabla->index('estado', 'idx_empresas_estado');
            $tabla->index('rfc', 'idx_empresas_rfc');
        });

        EsquemaErp::check('empresas', 'chk_empresas_estado', "estado IN ('activo','inactivo')");

        Schema::create('prospectos', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->foreignId('organizacion_id')->nullable()->constrained('organizaciones')->nullOnDelete();
            $tabla->string('origen', 20)->default('otro');
            $tabla->string('nombre', 100);
            $tabla->string('apellidos', 100)->nullable();
            $tabla->string('empresa_nombre', 200)->nullable();
            $tabla->string('correo', 150)->nullable();
            $tabla->string('telefono', 30)->nullable();
            $tabla->string('estado', 20)->default('nuevo');
            $tabla->foreignId('asignado_a')->nullable()->constrained('users')->nullOnDelete();
            EsquemaErp::dinero($tabla, 'valor_estimado')->default(0);
            $tabla->text('notas')->nullable();
            $tabla->foreignId('cliente_convertido_id')->nullable()->constrained('clientes')->nullOnDelete();
            $tabla->timestamp('convertido_en')->nullable();
            $tabla->string('motivo_perdida', 300)->nullable();
            EsquemaErp::auditoria($tabla);

            $tabla->index('estado', 'idx_prospectos_estado');
            $tabla->index('asignado_a', 'idx_prospectos_asignado');
        });

        EsquemaErp::check('prospectos', 'chk_prospectos_origen', "origen IN ('web','referido','llamada','evento','feria','otro')");
        EsquemaErp::check('prospectos', 'chk_prospectos_estado', "estado IN ('nuevo','contactado','calificado','convertido','perdido')");
        EsquemaErp::check('prospectos', 'chk_prospectos_valor', 'valor_estimado >= 0');
        // "Un prospecto convertido debe apuntar a un cliente" NO es un CHECK a
        // proposito: cliente_convertido_id es ON DELETE SET NULL, asi que borrar
        // al cliente dejaria al prospecto violandolo (MariaDB de hecho rechaza
        // la combinacion). Lo garantiza ServicioConversionProspecto.

        Schema::create('contactos', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->foreignId('empresa_id')->nullable()->constrained('empresas')->nullOnDelete();
            $tabla->foreignId('cliente_id')->nullable()->constrained('clientes')->nullOnDelete();
            $tabla->foreignId('prospecto_id')->nullable()->constrained('prospectos')->nullOnDelete();
            $tabla->string('nombre', 100);
            $tabla->string('apellidos', 100)->nullable();
            $tabla->string('correo', 150)->nullable();
            $tabla->string('telefono', 30)->nullable();
            $tabla->string('puesto', 100)->nullable();
            $tabla->boolean('es_principal')->default(false);
            EsquemaErp::auditoria($tabla);

            $tabla->index('empresa_id', 'idx_contactos_empresa');
            $tabla->index('cliente_id', 'idx_contactos_cliente');
        });

        Schema::create('oportunidades', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->foreignId('organizacion_id')->nullable()->constrained('organizaciones')->nullOnDelete();
            $tabla->foreignId('prospecto_id')->nullable()->constrained('prospectos')->nullOnDelete();
            $tabla->foreignId('cliente_id')->nullable()->constrained('clientes')->nullOnDelete();
            $tabla->foreignId('contacto_id')->nullable()->constrained('contactos')->nullOnDelete();
            $tabla->string('nombre', 200);
            $tabla->text('descripcion')->nullable();
            $tabla->string('etapa', 20)->default('prospeccion');
            EsquemaErp::dinero($tabla, 'monto')->default(0);
            $tabla->char('moneda', 3)->default('MXN');
            $tabla->decimal('probabilidad', 5, 2)->default(0);
            $tabla->date('fecha_cierre_estimada')->nullable();
            $tabla->foreignId('asignado_a')->nullable()->constrained('users')->nullOnDelete();
            $tabla->foreignId('pedido_id')->nullable()->constrained('pedidos')->nullOnDelete();
            $tabla->timestamp('ganada_en')->nullable();
            $tabla->timestamp('perdida_en')->nullable();
            $tabla->string('motivo_perdida', 300)->nullable();
            EsquemaErp::auditoria($tabla);

            $tabla->index('etapa', 'idx_oportunidades_etapa');
            $tabla->index('asignado_a', 'idx_oportunidades_asignado');
            $tabla->index('cliente_id', 'idx_oportunidades_cliente');
            $tabla->index('fecha_cierre_estimada', 'idx_oportunidades_cierre');
        });

        EsquemaErp::check('oportunidades', 'chk_oportunidades_etapa', "etapa IN ('prospeccion','calificacion','propuesta','negociacion','ganada','perdida')");
        EsquemaErp::check('oportunidades', 'chk_oportunidades_valores', 'monto >= 0 AND probabilidad BETWEEN 0 AND 100');
        EsquemaErp::check('oportunidades', 'chk_oportunidades_ganada', "etapa <> 'ganada' OR ganada_en IS NOT NULL");
        EsquemaErp::check('oportunidades', 'chk_oportunidades_perdida', "etapa <> 'perdida' OR perdida_en IS NOT NULL");

        Schema::create('actividades', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->string('tipo_actividad', 20);
            $tabla->string('entidad_tipo', 100);
            $tabla->unsignedBigInteger('entidad_id');
            $tabla->string('resumen', 300);
            $tabla->text('resultado')->nullable();
            $tabla->timestamp('programada_en')->nullable();
            $tabla->timestamp('completada_en')->nullable();
            $tabla->foreignId('asignada_a')->nullable()->constrained('users')->nullOnDelete();
            EsquemaErp::auditoria($tabla);

            $tabla->index(['entidad_tipo', 'entidad_id', 'created_at'], 'idx_actividades_entidad');
            $tabla->index(['asignada_a', 'programada_en'], 'idx_actividades_asignada');
        });

        EsquemaErp::check('actividades', 'chk_actividades_tipo', "tipo_actividad IN ('llamada','correo','reunion','nota','tarea')");

        Schema::create('notas_crm', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->string('entidad_tipo', 100);
            $tabla->unsignedBigInteger('entidad_id');
            $tabla->foreignId('autor_id')->constrained('users')->cascadeOnDelete();
            $tabla->text('cuerpo');
            $tabla->boolean('fijada')->default(false);
            $tabla->timestamps();
            $tabla->softDeletes();

            $tabla->index(['entidad_tipo', 'entidad_id', 'created_at'], 'idx_notas_crm_entidad');
        });

        Schema::create('tareas', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->string('titulo', 200);
            $tabla->text('descripcion')->nullable();
            $tabla->string('entidad_tipo', 100)->nullable();
            $tabla->unsignedBigInteger('entidad_id')->nullable();
            $tabla->foreignId('asignada_a')->nullable()->constrained('users')->nullOnDelete();
            $tabla->date('fecha_limite')->nullable();
            $tabla->string('prioridad', 20)->default('media');
            $tabla->string('estado', 20)->default('pendiente');
            $tabla->timestamp('completada_en')->nullable();
            EsquemaErp::auditoria($tabla);

            $tabla->index(['asignada_a', 'estado', 'fecha_limite'], 'idx_tareas_asignada');
            $tabla->index(['entidad_tipo', 'entidad_id'], 'idx_tareas_entidad');
        });

        EsquemaErp::check('tareas', 'chk_tareas_prioridad', "prioridad IN ('baja','media','alta')");
        EsquemaErp::check('tareas', 'chk_tareas_estado', "estado IN ('pendiente','en_proceso','completada','cancelada')");
        EsquemaErp::check('tareas', 'chk_tareas_completada', "estado <> 'completada' OR completada_en IS NOT NULL");
    }

    public function down(): void
    {
        Schema::dropIfExists('tareas');
        Schema::dropIfExists('notas_crm');
        Schema::dropIfExists('actividades');
        Schema::dropIfExists('oportunidades');
        Schema::dropIfExists('contactos');
        Schema::dropIfExists('prospectos');
        Schema::dropIfExists('empresas');
    }
};

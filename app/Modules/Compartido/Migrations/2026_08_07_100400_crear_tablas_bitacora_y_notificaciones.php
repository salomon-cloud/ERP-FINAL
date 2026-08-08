<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * bitacora_auditoria es de SOLO INSERCION: tiene created_at pero no updated_at,
 * no deleted_at y no existe ninguna ruta, servicio ni comando que la modifique
 * o la borre.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bitacora_auditoria', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $tabla->string('modulo', 50);
            // creado|actualizado|eliminado|contabilizado|aprobado|cancelado|...
            $tabla->string('accion', 30);
            $tabla->string('entidad_tipo', 100);
            $tabla->unsignedBigInteger('entidad_id');
            $tabla->json('valores_anteriores')->nullable();
            $tabla->json('valores_nuevos')->nullable();
            $tabla->string('direccion_ip', 45)->nullable();
            $tabla->text('agente_usuario')->nullable();
            $tabla->timestamp('created_at')->useCurrent();

            $tabla->index(['entidad_tipo', 'entidad_id', 'created_at'], 'idx_bitacora_entidad');
            $tabla->index(['modulo', 'created_at'], 'idx_bitacora_modulo');
        });

        Schema::create('notificaciones', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            // stock_bajo|permiso_aprobado|pedido_contabilizado|factura_por_vencer|...
            $tabla->string('tipo', 50);
            $tabla->string('titulo', 200);
            $tabla->text('cuerpo')->nullable();
            $tabla->json('datos')->nullable();
            $tabla->timestamp('leida_en')->nullable();
            $tabla->timestamp('created_at')->useCurrent();

            $tabla->index(['user_id', 'leida_en'], 'idx_notificaciones_sin_leer');
            $tabla->index('created_at', 'idx_notificaciones_fecha');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notificaciones');
        Schema::dropIfExists('bitacora_auditoria');
    }
};

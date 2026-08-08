<?php

declare(strict_types=1);

use App\Modules\Compartido\Support\EsquemaErp;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Normaliza la autorizacion (mejora #1 de PLANNING). La columna users.role de
 * v1 se queda y sigue respondiendo hasAnyRole(), asi que ninguna ruta ni vista
 * v1 cambia; los modulos nuevos verifican privilegios.
 *
 * Se llaman `privilegios` y no `permisos` porque SISEN v1 ya usa `permisos`
 * para las solicitudes de permiso y vacaciones de los empleados.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->string('codigo', 50);
            $tabla->string('nombre', 100);
            $tabla->text('descripcion')->nullable();
            $tabla->boolean('es_sistema')->default(false);
            EsquemaErp::auditoria($tabla);
        });

        EsquemaErp::unicoActivo('roles', 'codigo', 'uq_roles_codigo', 'VARCHAR(50)');

        Schema::create('privilegios', function (Blueprint $tabla) {
            $tabla->id();
            // Codificado <modulo>.<entidad>.<accion>: finanzas.polizas.contabilizar
            $tabla->string('codigo', 100);
            $tabla->string('modulo', 50);
            $tabla->text('descripcion')->nullable();
            EsquemaErp::auditoria($tabla);

            $tabla->index('modulo', 'idx_privilegios_modulo');
        });

        EsquemaErp::unicoActivo('privilegios', 'codigo', 'uq_privilegios_codigo', 'VARCHAR(100)');

        Schema::create('rol_privilegios', function (Blueprint $tabla) {
            $tabla->foreignId('rol_id')->constrained('roles')->cascadeOnDelete();
            $tabla->foreignId('privilegio_id')->constrained('privilegios')->cascadeOnDelete();
            $tabla->timestamp('created_at')->useCurrent();

            $tabla->primary(['rol_id', 'privilegio_id']);
            $tabla->index('privilegio_id', 'idx_rol_privilegios_privilegio');
        });

        Schema::create('usuario_roles', function (Blueprint $tabla) {
            $tabla->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $tabla->foreignId('rol_id')->constrained('roles')->cascadeOnDelete();
            $tabla->timestamp('created_at')->useCurrent();

            $tabla->primary(['user_id', 'rol_id']);
            $tabla->index('rol_id', 'idx_usuario_roles_rol');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usuario_roles');
        Schema::dropIfExists('rol_privilegios');
        Schema::dropIfExists('privilegios');
        Schema::dropIfExists('roles');
    }
};

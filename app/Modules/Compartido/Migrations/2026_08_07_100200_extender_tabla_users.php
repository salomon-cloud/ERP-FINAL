<?php

declare(strict_types=1);

use App\Modules\Compartido\Support\EsquemaErp;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * users: la tabla de usuarios de SISEN v1 ES la tabla de usuarios del ERP, asi
 * que esto es un ALTER y nunca un CREATE. name, email, password, role y estado
 * siguen funcionando igual y ninguna pantalla v1 se toca.
 *
 * `users` conserva su nombre en ingles por ser una tabla del framework
 * (igual que sessions, cache, jobs y migrations); todo lo demas va en espanol.
 *
 * Tambien cierra la mejora #3 de PLANNING: users.empleado_id no tenia llave
 * foranea hacia empleados.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $tabla) {
            $tabla->foreignId('organizacion_id')->nullable()->after('id')
                ->constrained('organizaciones')->nullOnDelete();
            $tabla->string('telefono', 30)->nullable()->after('email');
            $tabla->string('avatar_url', 500)->nullable()->after('telefono');
            $tabla->boolean('debe_cambiar_password')->default(false)->after('estado');
            $tabla->timestamp('password_cambiado_en')->nullable()->after('debe_cambiar_password');
            $tabla->timestamp('ultimo_acceso_en')->nullable()->after('password_cambiado_en');
            $tabla->integer('intentos_fallidos')->default(0)->after('ultimo_acceso_en');
            $tabla->timestamp('bloqueado_en')->nullable()->after('intentos_fallidos');
            $tabla->softDeletes();
            EsquemaErp::autores($tabla);

            $tabla->index('estado', 'idx_users_estado');
        });

        // Al poder borrarse logicamente un usuario, la unicidad del correo debe
        // ignorar los borrados; si no, su direccion queda quemada para siempre.
        Schema::table('users', function (Blueprint $tabla) {
            $tabla->dropUnique('users_email_unique');
        });

        EsquemaErp::unicoActivo('users', 'email', 'uq_users_email_activo', 'VARCHAR(255)');
        EsquemaErp::llaveForaneaDiferida('users', 'empleado_id', 'empleados', 'fk_users_empleado');
    }

    public function down(): void
    {
        EsquemaErp::eliminarLlaveForaneaDiferida('users', 'fk_users_empleado');

        Schema::table('users', function (Blueprint $tabla) {
            $tabla->dropIndex('uq_users_email_activo');
            $tabla->dropColumn('email_activo');
            $tabla->unique('email', 'users_email_unique');
            $tabla->dropIndex('idx_users_estado');
            $tabla->dropConstrainedForeignId('organizacion_id');
            $tabla->dropConstrainedForeignId('creado_por');
            $tabla->dropConstrainedForeignId('actualizado_por');
            $tabla->dropSoftDeletes();
            $tabla->dropColumn([
                'telefono',
                'avatar_url',
                'debe_cambiar_password',
                'password_cambiado_en',
                'ultimo_acceso_en',
                'intentos_fallidos',
                'bloqueado_en',
            ]);
        });
    }
};

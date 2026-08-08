<?php

declare(strict_types=1);

use App\Modules\Compartido\Support\EsquemaErp;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * configuraciones: lo que un administrador puede cambiar en caliente,
 * direccionado como grupo.clave. Los valores estaticos por omision viven en
 * config/sisen.php y son el respaldo.
 *
 * catalogos: listas de valores compartidas entre modulos. Un valor que ademas
 * tiene atributos o relaciones propias merece su tabla de dominio.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('configuraciones', function (Blueprint $tabla) {
            $tabla->id();
            // empresa|finanzas|inventario|ventas|notificaciones|seguridad
            $tabla->string('grupo', 50);
            $tabla->string('clave', 100);
            $tabla->text('valor')->nullable();
            $tabla->boolean('es_json')->default(false);
            $tabla->text('descripcion')->nullable();
            EsquemaErp::auditoria($tabla);

            $tabla->unique(['grupo', 'clave'], 'uq_configuraciones_grupo_clave');
        });

        Schema::create('catalogos', function (Blueprint $tabla) {
            $tabla->id();
            // condiciones_pago|forma_pago|moneda|tipo_contrato|motivo_descuento
            $tabla->string('grupo', 50);
            $tabla->string('codigo', 50);
            $tabla->string('nombre', 150);
            $tabla->string('valor', 255)->nullable();
            $tabla->integer('orden')->default(0);
            $tabla->boolean('activo')->default(true);
            EsquemaErp::auditoria($tabla);

            $tabla->unique(['grupo', 'codigo'], 'uq_catalogos_grupo_codigo');
            $tabla->index(['grupo', 'activo', 'orden'], 'idx_catalogos_grupo_activo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalogos');
        Schema::dropIfExists('configuraciones');
    }
};

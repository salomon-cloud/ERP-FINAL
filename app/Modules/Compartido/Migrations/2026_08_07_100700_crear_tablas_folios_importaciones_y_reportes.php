<?php

declare(strict_types=1);

use App\Modules\Compartido\Support\EsquemaErp;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * secuencias_documento es la UNICA fuente de folios visibles. En todo el ERP
 * jamas se le muestra a un usuario un id autoincremental, y un documento
 * cancelado nunca devuelve su folio al pozo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('secuencias_documento', function (Blueprint $tabla) {
            $tabla->id();
            // pedidos, facturas, polizas, ordenes_compra, ...
            $tabla->string('modulo', 50);
            $tabla->string('prefijo', 10);
            $tabla->string('sufijo', 10)->default('');
            $tabla->unsignedBigInteger('numero_actual')->default(0);
            $tabla->smallInteger('relleno')->default(6);
            $tabla->boolean('activo')->default(true);
            $tabla->timestamps();

            $tabla->unique(['modulo', 'prefijo'], 'uq_secuencias_modulo_prefijo');
        });

        Schema::create('lotes_importacion', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->string('modulo', 50);
            $tabla->string('plantilla', 100);
            $tabla->string('ruta_archivo', 500)->nullable();
            $tabla->string('estado', 20)->default('cargado');
            $tabla->integer('filas_totales')->default(0);
            $tabla->integer('filas_insertadas')->default(0);
            $tabla->integer('filas_actualizadas')->default(0);
            $tabla->integer('filas_omitidas')->default(0);
            $tabla->json('resumen_errores')->nullable();
            $tabla->foreignId('importado_por')->nullable()->constrained('users')->nullOnDelete();
            $tabla->timestamp('created_at')->useCurrent();
            $tabla->timestamp('completado_en')->nullable();
        });

        EsquemaErp::check(
            'lotes_importacion',
            'chk_lotes_importacion_estado',
            "estado IN ('cargado','validando','importado','fallido','cancelado')"
        );

        Schema::create('reportes_guardados', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->string('nombre', 150);
            $tabla->string('modulo', 50);
            // balanza_comprobacion, existencias, ...
            $tabla->string('tipo_reporte', 50);
            $tabla->json('configuracion')->nullable();
            $tabla->boolean('compartido')->default(false);
            $tabla->foreignId('creado_por')->nullable()->constrained('users')->cascadeOnDelete();
            $tabla->timestamps();
            $tabla->softDeletes();

            $tabla->index(['creado_por', 'modulo'], 'idx_reportes_guardados_dueno');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reportes_guardados');
        Schema::dropIfExists('lotes_importacion');
        Schema::dropIfExists('secuencias_documento');
    }
};

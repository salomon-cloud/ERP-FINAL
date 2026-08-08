<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Las cinco son polimorficas: se enganchan a cualquier entidad de cualquier
 * modulo sin que ese modulo tenga que saber que existen.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('adjuntos', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->string('modelo_tipo', 100);
            $tabla->unsignedBigInteger('modelo_id');
            $tabla->string('disco', 50)->default('public');
            $tabla->string('ruta', 500);
            $tabla->string('nombre_original', 255);
            $tabla->string('tipo_mime', 120)->nullable();
            $tabla->unsignedBigInteger('tamano_bytes')->nullable();
            $tabla->foreignId('subido_por')->nullable()->constrained('users')->nullOnDelete();
            $tabla->timestamp('created_at')->useCurrent();
            $tabla->softDeletes();

            $tabla->index(['modelo_tipo', 'modelo_id'], 'idx_adjuntos_modelo');
        });

        Schema::create('etiquetas', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->string('nombre', 80);
            $tabla->string('slug', 100);
            $tabla->string('color', 20)->nullable();
            $tabla->timestamp('created_at')->useCurrent();
            $tabla->softDeletes();

            $tabla->unique('slug', 'uq_etiquetas_slug');
        });

        Schema::create('etiquetables', function (Blueprint $tabla) {
            $tabla->foreignId('etiqueta_id')->constrained('etiquetas')->cascadeOnDelete();
            $tabla->string('etiquetable_tipo', 100);
            $tabla->unsignedBigInteger('etiquetable_id');
            $tabla->timestamp('created_at')->useCurrent();

            $tabla->primary(['etiqueta_id', 'etiquetable_tipo', 'etiquetable_id'], 'pk_etiquetables');
            $tabla->index(['etiquetable_tipo', 'etiquetable_id'], 'idx_etiquetables_entidad');
        });

        Schema::create('favoritos', function (Blueprint $tabla) {
            $tabla->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $tabla->string('favorito_tipo', 100);
            $tabla->unsignedBigInteger('favorito_id');
            $tabla->timestamp('created_at')->useCurrent();

            $tabla->primary(['user_id', 'favorito_tipo', 'favorito_id'], 'pk_favoritos');
            $tabla->index(['favorito_tipo', 'favorito_id'], 'idx_favoritos_entidad');
        });

        Schema::create('comentarios', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->string('comentable_tipo', 100);
            $tabla->unsignedBigInteger('comentable_id');
            $tabla->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $tabla->foreignId('padre_id')->nullable()->constrained('comentarios')->cascadeOnDelete();
            $tabla->text('cuerpo');
            $tabla->timestamps();
            $tabla->softDeletes();

            $tabla->index(['comentable_tipo', 'comentable_id', 'created_at'], 'idx_comentarios_entidad');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comentarios');
        Schema::dropIfExists('favoritos');
        Schema::dropIfExists('etiquetables');
        Schema::dropIfExists('etiquetas');
        Schema::dropIfExists('adjuntos');
    }
};

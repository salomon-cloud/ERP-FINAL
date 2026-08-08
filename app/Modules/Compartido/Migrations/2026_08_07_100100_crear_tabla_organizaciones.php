<?php

declare(strict_types=1);

use App\Modules\Compartido\Support\EsquemaErp;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * organizaciones: la empresa que opera el ERP.
 *
 * Hoy se trabaja con una sola empresa. Las tablas transaccionales llevan
 * organizacion_id nullable para poder activar multiempresa mas adelante sin
 * romper el flujo actual.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizaciones', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->string('codigo', 20);
            $tabla->string('nombre', 200);
            $tabla->string('rfc', 30)->nullable();
            $tabla->string('razon_social', 200)->nullable();
            $tabla->text('direccion')->nullable();
            $tabla->string('telefono', 30)->nullable();
            $tabla->string('correo', 150)->nullable();
            $tabla->string('logo_url', 500)->nullable();
            $tabla->char('moneda', 3)->default('MXN');
            $tabla->smallInteger('mes_inicio_ejercicio')->default(1);
            $tabla->boolean('activo')->default(true);
            EsquemaErp::auditoria($tabla);
        });

        EsquemaErp::unicoActivo('organizaciones', 'codigo', 'uq_organizaciones_codigo', 'VARCHAR(20)');
        EsquemaErp::check('organizaciones', 'chk_organizaciones_mes_inicio', 'mes_inicio_ejercicio BETWEEN 1 AND 12');
    }

    public function down(): void
    {
        Schema::dropIfExists('organizaciones');
    }
};

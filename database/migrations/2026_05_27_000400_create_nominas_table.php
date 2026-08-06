<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nominas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empleado_id')->constrained('empleados')->cascadeOnDelete();
            $table->string('periodo_pago');
            $table->date('fecha_pago');
            $table->decimal('sueldo_base', 12, 2);
            $table->decimal('bonos', 12, 2)->default(0);
            $table->decimal('horas_extra', 12, 2)->default(0);
            $table->decimal('deducciones', 12, 2)->default(0);
            $table->decimal('isr', 12, 2)->default(0);
            $table->decimal('imss', 12, 2)->default(0);
            $table->decimal('total_pagar', 12, 2)->default(0);
            $table->enum('estado', ['pendiente', 'pagada', 'cancelada'])->default('pendiente');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nominas');
    }
};

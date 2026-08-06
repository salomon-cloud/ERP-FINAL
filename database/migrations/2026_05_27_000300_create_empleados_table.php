<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('empleados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('departamento_id')->constrained('departamentos')->restrictOnDelete();
            $table->foreignId('puesto_id')->constrained('puestos')->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('nombre');
            $table->string('apellidos');
            $table->string('curp', 18)->unique();
            $table->string('rfc', 13)->unique();
            $table->string('correo')->unique();
            $table->string('telefono')->nullable();
            $table->text('direccion')->nullable();
            $table->date('fecha_nacimiento');
            $table->date('fecha_contratacion');
            $table->decimal('sueldo_base', 12, 2);
            $table->enum('estado', ['activo', 'inactivo'])->default('activo');
            $table->string('fotografia')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('empleados');
    }
};

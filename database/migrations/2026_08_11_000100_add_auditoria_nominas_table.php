<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nominas', function (Blueprint $table) {
            $table->enum('metodo_pago', ['transferencia', 'efectivo', 'cheque'])->nullable()->after('total_pagar');
            $table->foreignId('created_by')->nullable()->after('metodo_pago')->constrained('users')->nullOnDelete();
            $table->foreignId('paid_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            $table->timestamp('fecha_pago_real')->nullable()->after('paid_by');
        });
    }

    public function down(): void
    {
        Schema::table('nominas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by');
            $table->dropConstrainedForeignId('paid_by');
            $table->dropColumn(['metodo_pago', 'fecha_pago_real']);
        });
    }
};

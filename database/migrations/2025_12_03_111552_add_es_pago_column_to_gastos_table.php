<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('gastos_compartidos', function (Blueprint $table) {
            $table->boolean('es_pago_balance')->default(false)->after('icono');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gastos_compartidos', function (Blueprint $table) {
            $table->dropColumn('es_pago_balance');
        });
    }
};

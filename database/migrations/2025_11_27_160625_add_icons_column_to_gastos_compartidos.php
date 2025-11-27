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
            $table->string('icono', 20)->default('Coins')->after('descripcion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gastos_compartidos', function (Blueprint $table) {
            $table->dropColumn('icono');
        });
    }
};

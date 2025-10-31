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
        Schema::create('grupo_gasto_participantes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grupo_gasto_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('rol')->default('miembro');
            $table->timestamps();

            // Evitar duplicados
            $table->unique(['grupo_gasto_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grupo_gasto_participantes');
    }
};

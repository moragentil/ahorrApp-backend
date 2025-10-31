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
        Schema::create('invitaciones_grupos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grupo_gasto_id')->constrained('grupo_gastos')->onDelete('cascade');
            $table->string('email');
            $table->foreignId('invitado_por')->constrained('users')->onDelete('cascade');
            $table->enum('estado', ['pendiente', 'aceptada', 'rechazada'])->default('pendiente');
            $table->string('token')->unique();
            $table->timestamp('expira_en')->nullable();
            $table->timestamps();

            $table->index(['email', 'grupo_gasto_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invitaciones_grupos');
    }
};

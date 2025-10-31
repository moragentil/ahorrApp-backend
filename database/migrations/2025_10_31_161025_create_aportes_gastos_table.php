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
        Schema::create('aportes_gastos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gasto_compartido_id')->constrained('gastos_compartidos')->onDelete('cascade');
            $table->foreignId('participante_id')->constrained('participantes')->onDelete('cascade');
            $table->decimal('monto_asignado', 10, 2);
            $table->decimal('monto_pagado', 10, 2)->default(0);
            $table->enum('estado', ['pendiente', 'pagado'])->default('pendiente');
            $table->timestamps();

            $table->index(['gasto_compartido_id', 'participante_id']);
            $table->unique(['gasto_compartido_id', 'participante_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aportes_gastos');
    }
};

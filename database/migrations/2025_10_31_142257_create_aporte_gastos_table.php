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
        Schema::create('aporte_gastos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gasto_compartido_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('monto_esperado', 10, 2);
            $table->decimal('monto_pagado', 10, 2)->default(0);
            $table->timestamps();

            // Evitar duplicados
            $table->unique(['gasto_compartido_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aporte_gastos');
    }
};

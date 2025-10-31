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
        Schema::create('gasto_compartidos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grupo_gasto_id')->constrained()->cascadeOnDelete();
            $table->string('descripcion');
            $table->decimal('monto_total', 10, 2);
            $table->foreignId('pagado_por')->constrained('users')->cascadeOnDelete();
            $table->date('fecha');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gasto_compartidos');
    }
};

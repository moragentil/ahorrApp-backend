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
        Schema::create('gastos_compartidos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grupo_gasto_id')->constrained('grupo_gastos')->onDelete('cascade');
            $table->foreignId('pagado_por_participante_id')->constrained('participantes')->onDelete('cascade');
            $table->string('descripcion');
            $table->decimal('monto_total', 10, 2);
            $table->date('fecha');
            $table->timestamps();

            $table->index(['grupo_gasto_id', 'fecha']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gastos_compartidos');
    }
};

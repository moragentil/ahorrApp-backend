<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AporteGasto extends Model
{
    use HasFactory;

    protected $table = 'aportes_gastos';

    protected $fillable = [
        'gasto_compartido_id',
        'participante_id',
        'monto_asignado',
        'monto_pagado',
        'estado', // pendiente, pagado
    ];

    protected $casts = [
        'monto_asignado' => 'decimal:2',
        'monto_pagado' => 'decimal:2',
    ];

    public function gastoCompartido()
    {
        return $this->belongsTo(GastoCompartido::class, 'gasto_compartido_id');
    }

    public function participante()
    {
        return $this->belongsTo(Participante::class, 'participante_id');
    }

    public function getSaldoAttribute()
    {
        return $this->monto_asignado - $this->monto_pagado;
    }
}
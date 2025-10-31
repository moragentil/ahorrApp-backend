<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AporteGasto extends Model
{
    use HasFactory;

    protected $fillable = [
        'gasto_compartido_id',
        'user_id',
        'monto_esperado',
        'monto_pagado',
    ];

    protected $casts = [
        'monto_esperado' => 'decimal:2',
        'monto_pagado' => 'decimal:2',
    ];

    public function gasto()
    {
        return $this->belongsTo(GastoCompartido::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class);
    }
}
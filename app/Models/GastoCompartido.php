<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GastoCompartido extends Model
{
    use HasFactory;

    protected $fillable = [
        'grupo_gasto_id',
        'descripcion',
        'monto_total',
        'pagado_por', // user_id de quien pagó
        'fecha',
    ];

    protected $casts = [
        'fecha' => 'date',
        'monto_total' => 'decimal:2',
    ];

    public function grupo()
    {
        return $this->belongsTo(GrupoGasto::class);
    }

    public function pagador()
    {
        return $this->belongsTo(User::class, 'pagado_por');
    }

    public function aportes()
    {
        return $this->hasMany(AporteGasto::class);
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GastoCompartido extends Model
{
    use HasFactory;

    protected $table = 'gastos_compartidos';

    protected $fillable = [
        'grupo_gasto_id',
        'pagado_por_participante_id', // ID del participante que pagó
        'descripcion',
        'monto_total',
        'fecha',
    ];

    protected $casts = [
        'fecha' => 'date',
        'monto_total' => 'decimal:2',
    ];

    public function grupo()
    {
        return $this->belongsTo(GrupoGasto::class, 'grupo_gasto_id');
    }

    public function pagador()
    {
        return $this->belongsTo(Participante::class, 'pagado_por_participante_id');
    }

    public function aportes()
    {
        return $this->hasMany(AporteGasto::class, 'gasto_compartido_id');
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ahorro extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'nombre',
        'descripcion',
        'monto_objetivo',
        'monto_actual',
        'fecha_limite',
        'prioridad',
        'estado',
        'color',
    ];

    protected $casts = [
        'fecha_limite' => 'date',
        'monto_objetivo' => 'decimal:2',
        'monto_actual' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
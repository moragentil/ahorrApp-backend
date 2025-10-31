<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GrupoGasto extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'descripcion',
        'creado_por', // user_id del creador
    ];

    public function participantes()
    {
        return $this->belongsToMany(User::class, 'grupo_gasto_participantes')
                    ->withPivot('rol') // opcional: 'admin', 'miembro'
                    ->withTimestamps();
    }

    public function gastos()
    {
        return $this->hasMany(GastoCompartido::class);
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'creado_por');
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GrupoGasto extends Model
{
    use HasFactory;

    protected $table = 'grupo_gastos';

    protected $fillable = [
        'nombre',
        'descripcion',
        'creador_id',
        'estado',
    ];

    public function creador()
    {
        return $this->belongsTo(User::class, 'creador_id');
    }

    // Usuarios que pueden ver/gestionar el grupo
    public function miembros()
    {
        return $this->belongsToMany(User::class, 'grupo_gasto_miembros', 'grupo_gasto_id', 'user_id')
                    ->withTimestamps();
    }

    // Todos los participantes del grupo (usuarios y no usuarios)
    public function participantes()
    {
        return $this->hasMany(Participante::class, 'grupo_gasto_id');
    }

    public function gastosCompartidos()
    {
        return $this->hasMany(GastoCompartido::class, 'grupo_gasto_id');
    }

    public function invitaciones()
    {
        return $this->hasMany(InvitacionGrupo::class, 'grupo_gasto_id');
    }
}
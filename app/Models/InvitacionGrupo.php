<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvitacionGrupo extends Model
{
    use HasFactory;

    protected $table = 'invitaciones_grupos';

    protected $fillable = [
        'grupo_gasto_id',
        'email',
        'user_id',
        'invitado_por',
        'estado', // pendiente, aceptada, rechazada
        'token',
        'expira_en',
    ];

    protected $casts = [
        'expira_en' => 'datetime',
    ];

    public function grupo()
    {
        return $this->belongsTo(GrupoGasto::class, 'grupo_gasto_id');
    }

    public function invitador()
    {
        return $this->belongsTo(User::class, 'invitado_por');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function estaExpirada()
    {
        return $this->expira_en && now()->isAfter($this->expira_en);
    }
}
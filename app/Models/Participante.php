<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Participante extends Model
{
    use HasFactory;

    protected $fillable = [
        'grupo_gasto_id',
        'nombre',
        'email',
        'user_id', // NULL si no es usuario del sistema
    ];

    public function grupo()
    {
        return $this->belongsTo(GrupoGasto::class, 'grupo_gasto_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function gastosRealizados()
    {
        return $this->hasMany(GastoCompartido::class, 'pagado_por_participante_id');
    }

    public function aportes()
    {
        return $this->hasMany(AporteGasto::class, 'participante_id');
    }

    public function esUsuario()
    {
        return !is_null($this->user_id);
    }
}
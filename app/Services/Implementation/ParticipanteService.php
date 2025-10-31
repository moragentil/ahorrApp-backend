<?php

namespace App\Services\Implementation;

use App\Models\Participante;
use App\Services\Interface\ParticipanteServiceInterface;

class ParticipanteService implements ParticipanteServiceInterface
{
    public function all($grupoId)
    {
        return Participante::where('grupo_gasto_id', $grupoId)
            ->with('usuario')
            ->get();
    }

    public function find($id)
    {
        return Participante::with('usuario')->findOrFail($id);
    }

    public function create(array $data)
    {
        return Participante::create($data);
    }

    public function update($id, array $data)
    {
        $participante = Participante::findOrFail($id);
        $participante->update($data);
        return $participante;
    }

    public function delete($id)
    {
        $participante = Participante::findOrFail($id);
        $participante->delete();
        return true;
    }

    public function vincularUsuario($participanteId, $userId)
    {
        $participante = Participante::findOrFail($participanteId);
        $participante->update(['user_id' => $userId]);
        return $participante->fresh('usuario');
    }
}
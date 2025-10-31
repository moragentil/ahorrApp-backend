<?php

namespace App\Services\Interface;

interface GrupoGastoServiceInterface
{
    public function all($userId);
    public function find($id);
    public function create(array $data);
    public function update($id, array $data);
    public function delete($id);
    public function addParticipante($grupoId, $userId, $rol = 'miembro');
    public function removeParticipante($grupoId, $userId);
    public function calcularBalances($grupoId);
}
<?php
// filepath: c:\Users\morag\Desktop\TP5\ahorrApp-backend\app\Services\Interface\ParticipanteServiceInterface.php

namespace App\Services\Interface;

interface ParticipanteServiceInterface
{
    public function all($grupoId);
    public function find($id);
    public function create(array $data);
    public function update($id, array $data);
    public function delete($id);
    public function vincularUsuario($participanteId, $userId);
}
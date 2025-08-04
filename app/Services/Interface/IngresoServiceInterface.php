<?php

namespace App\Services\Interface;

interface IngresoServiceInterface
{
    public function all($userId);
    public function find($id);
    public function create(array $data);
    public function update($id, array $data);
    public function delete($id);
    public function estadisticas(\App\Models\User $user, $month = null, $year = null);
}
<?php

namespace App\Services\Interface;

interface GastoCompartidoServiceInterface
{
    public function all($grupoId);
    public function find($id);
    public function create(array $data);
    public function update($id, array $data);
    public function delete($id);
    public function registrarAportes($gastoId, array $aportes);
}
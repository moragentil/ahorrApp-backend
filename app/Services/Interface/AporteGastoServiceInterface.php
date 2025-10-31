<?php

namespace App\Services\Interface;

interface AporteGastoServiceInterface
{
    public function all($gastoCompartidoId);
    public function find($id);
    public function create(array $data);
    public function update($id, array $data);
    public function delete($id);
    public function registrarPago($id, $montoPagado);
}
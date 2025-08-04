<?php

namespace App\Services\Interface;

interface CategoriaServiceInterface
{
    public function all($userId);
    public function find($id);
    public function create(array $data);
    public function update($id, array $data);
    public function delete($id);
    public function getByTipo($tipo, $userId);
    public function resumenCategorias($userId);
}
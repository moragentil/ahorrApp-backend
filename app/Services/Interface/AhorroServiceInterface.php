<?php

namespace App\Services\Interface;

interface AhorroServiceInterface
{
    public function all();
    public function find($id);
    public function create(array $data);
    public function update($id, array $data);
    public function delete($id);
}
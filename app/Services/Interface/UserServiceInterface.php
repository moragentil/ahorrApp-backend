<?php

namespace App\Services\Interface;

interface UserServiceInterface
{
    public function all($userId);
    public function find($id);
    public function update($id, array $data);
    public function delete($id);
}
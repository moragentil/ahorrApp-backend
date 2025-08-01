<?php

namespace App\Services;

interface UserServiceInterface
{
    public function all();
    public function find($id);
    public function update($id, array $data);
    public function delete($id);
}
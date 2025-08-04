<?php

namespace App\Services\Implementation;

use App\Models\Ahorro;
use App\Services\Interface\AhorroServiceInterface;

class AhorroService implements AhorroServiceInterface
{
    public function all($userId)
    {
        return Ahorro::where('user_id', $userId)->get();
    }

    public function find($id)
    {
        return Ahorro::findOrFail($id);
    }

    public function create(array $data)
    {
        return Ahorro::create($data);
    }

    public function update($id, array $data)
    {
        $ahorro = Ahorro::findOrFail($id);
        $ahorro->update($data);
        return $ahorro;
    }

    public function delete($id)
    {
        $ahorro = Ahorro::findOrFail($id);
        $ahorro->delete();
        return true;
    }
}
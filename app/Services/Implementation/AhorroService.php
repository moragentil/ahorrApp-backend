<?php

namespace App\Services;

use App\Models\Ahorro;

class AhorroService implements AhorroServiceInterface
{
    public function all()
    {
        return Ahorro::all();
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
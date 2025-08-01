<?php

namespace App\Services;

use App\Models\Gasto;

class GastoService implements GastoServiceInterface
{
    public function all()
    {
        return Gasto::all();
    }

    public function find($id)
    {
        return Gasto::findOrFail($id);
    }

    public function create(array $data)
    {
        return Gasto::create($data);
    }

    public function update($id, array $data)
    {
        $gasto = Gasto::findOrFail($id);
        $gasto->update($data);
        return $gasto;
    }

    public function delete($id)
    {
        $gasto = Gasto::findOrFail($id);
        $gasto->delete();
        return true;
    }
}
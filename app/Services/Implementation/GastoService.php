<?php

namespace App\Services\Implementation;

use App\Models\Gasto;
use App\Services\Interface\GastoServiceInterface;

class GastoService implements GastoServiceInterface
{
    public function all()
    {
        return Gasto::with('categoria')->get();
    }

    public function find($id)
    {
        return Gasto::with('categoria')->findOrFail($id);
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
<?php

namespace App\Services;

use App\Models\Ingreso;

class IngresoService implements IngresoServiceInterface
{
    public function all()
    {
        return Ingreso::all();
    }

    public function find($id)
    {
        return Ingreso::findOrFail($id);
    }

    public function create(array $data)
    {
        return Ingreso::create($data);
    }

    public function update($id, array $data)
    {
        $ingreso = Ingreso::findOrFail($id);
        $ingreso->update($data);
        return $ingreso;
    }

    public function delete($id)
    {
        $ingreso = Ingreso::findOrFail($id);
        $ingreso->delete();
        return true;
    }
}
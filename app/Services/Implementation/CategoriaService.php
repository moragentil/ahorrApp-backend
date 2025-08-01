<?php

namespace App\Services;

use App\Models\Categoria;

class CategoriaService implements CategoriaServiceInterface
{
    public function all()
    {
        return Categoria::all();
    }

    public function find($id)
    {
        return Categoria::findOrFail($id);
    }

    public function create(array $data)
    {
        return Categoria::create($data);
    }

    public function update($id, array $data)
    {
        $categoria = Categoria::findOrFail($id);
        $categoria->update($data);
        return $categoria;
    }

    public function delete($id)
    {
        $categoria = Categoria::findOrFail($id);
        $categoria->delete();
        return true;
    }
}
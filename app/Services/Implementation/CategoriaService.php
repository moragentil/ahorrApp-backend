<?php

namespace App\Services\Implementation;

use App\Models\Categoria;
use App\Services\Interface\CategoriaServiceInterface;


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
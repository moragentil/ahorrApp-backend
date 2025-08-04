<?php

namespace App\Services\Implementation;

use App\Models\Categoria;
use App\Services\Interface\CategoriaServiceInterface;


class CategoriaService implements CategoriaServiceInterface
{
    public function all($userId)
    {
        return Categoria::where('user_id', $userId)->get();
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
    
    public function getByTipo($tipo, $userId)
    {
        return Categoria::where('tipo', $tipo)
            ->where('user_id', $userId)
            ->get();
    }
    
    public function resumenCategorias($userId)
    {
        $categorias = Categoria::where('user_id', $userId)->get();

        return $categorias->map(function ($cat) {
            if ($cat->tipo === 'gasto') {
                $total = $cat->gastos()->sum('monto');
                $transacciones = $cat->gastos()->count();
            } else {
                $total = $cat->ingresos()->sum('monto');
                $transacciones = $cat->ingresos()->count();
            }
            return [
                'id' => $cat->id,
                'nombre' => $cat->nombre,
                'tipo' => $cat->tipo,
                'color' => $cat->color,
                'total_gastado' => $total,
                'total_transacciones' => $transacciones,
            ];
        });
    }
}
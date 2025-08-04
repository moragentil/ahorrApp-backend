<?php

namespace App\Services\Implementation;

use App\Models\Gasto;
use App\Services\Interface\GastoServiceInterface;
use Illuminate\Support\Facades\DB;

class GastoService implements GastoServiceInterface
{
    public function all($userId)
    {
        return Gasto::with('categoria')->where('user_id', $userId)->get();
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

    public function topGastos($userId, $limit = 4)
    {
        $gastos = Gasto::select('descripcion', DB::raw('SUM(monto) as total'))
            ->where('user_id', $userId)
            ->groupBy('descripcion')
            ->orderByDesc('total')
            ->take($limit)
            ->get()
            ->map(function ($gasto) {
                return [
                    'descripcion' => $gasto->descripcion,
                    'total' => $gasto->total,
                ];
            });

        if ($gastos->isEmpty()) {
            // Gastos por defecto si el usuario no tiene movimientos
            $gastos = collect([
                ['descripcion' => 'Nafta', 'total' => 50000],
                ['descripcion' => 'Supermercado', 'total' => 100000],
                ['descripcion' => 'Café', 'total' => 5000],
                ['descripcion' => 'Internet', 'total' => 8000],
            ]);
        }

        return $gastos;
    }
}
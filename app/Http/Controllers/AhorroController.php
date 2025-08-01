<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\Interface\AhorroServiceInterface;

class AhorroController extends Controller
{
    protected $service;

    public function __construct(AhorroServiceInterface $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        return response()->json($this->service->all());
    }

    public function show($id)
    {
        return response()->json($this->service->find($id));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'nombre' => 'required|string',
            'descripcion' => 'string|nullable',
            'monto_objetivo' => 'required|numeric',
            'monto_actual' => 'numeric',
            'fecha_limite' => 'date|nullable',
            'prioridad' => 'required|in:Baja,Media,Alta',
            'estado' => 'required|in:Activo,Completado,Cancelado',
        ]);
        return response()->json($this->service->create($data), 201);
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'nombre' => 'string',
            'descripcion' => 'string|nullable',
            'monto_objetivo' => 'numeric',
            'monto_actual' => 'numeric',
            'fecha_limite' => 'date|nullable',
            'prioridad' => 'in:Baja,Media,Alta',
            'estado' => 'in:Activo,Completado,Cancelado',
        ]);
        return response()->json($this->service->update($id, $data));
    }

    public function destroy($id)
    {
        $this->service->delete($id);
        return response()->json(['message' => 'Deleted']);
    }
}
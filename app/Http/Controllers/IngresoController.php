<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\Interface\IngresoServiceInterface;
use App\Models\User;

class IngresoController extends Controller
{
    protected $service;

    public function __construct(IngresoServiceInterface $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $userId = $request->user()->id;
        return response()->json($this->service->all($userId));
    }

    public function show($id)
    {
        return response()->json($this->service->find($id));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'categoria_id' => 'required|exists:categorias,id',
            'descripcion' => 'required|string',
            'monto' => 'required|numeric',
            'fecha' => 'required|date',
        ]);
        return response()->json($this->service->create($data), 201);
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'categoria_id' => 'exists:categorias,id',
            'descripcion' => 'string',
            'monto' => 'numeric',
            'fecha' => 'date',
        ]);
        return response()->json($this->service->update($id, $data));
    }

    public function destroy($id)
    {
        $this->service->delete($id);
        return response()->json(['message' => 'Deleted']);
    }

    public function estadisticas(Request $request)
    {
        $user = $request->user();
        $month = $request->query('month');
        $year = $request->query('year');
        return response()->json($this->service->estadisticas($user, $month, $year));
    }
}
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\Interface\CategoriaServiceInterface;

class CategoriaController extends Controller
{
    protected $service;

    public function __construct(CategoriaServiceInterface $service)
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
            'user_id' => 'exists:users,id',
            'nombre' => 'required|string',
            'tipo' => 'required|in:gasto,ingreso',
            'color' => 'string|nullable',
        ]);
        return response()->json($this->service->create($data), 201);
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'nombre' => 'string',
            'tipo' => 'in:gasto,ingreso',
            'color' => 'string|nullable',
        ]);
        return response()->json($this->service->update($id, $data));
    }

    public function destroy($id)
    {
        $this->service->delete($id);
        return response()->json(['message' => 'Deleted']);
    }

    public function gastoCategorias()
    {
        return response()->json($this->service->getByTipo('gasto'));
    }

    public function ingresoCategorias()
    {
        return response()->json($this->service->getByTipo('ingreso'));
    }
    
    public function resumen(Request $request)
    {
        $userId = $request->user()->id;
        return response()->json($this->service->resumenCategorias($userId));
    }
}
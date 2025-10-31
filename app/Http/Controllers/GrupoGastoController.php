<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\Interface\GrupoGastoServiceInterface;

class GrupoGastoController extends Controller
{
    protected $service;

    public function __construct(GrupoGastoServiceInterface $service)
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
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
        ]);

        $data['creado_por'] = $request->user()->id;

        return response()->json($this->service->create($data), 201);
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'nombre' => 'string|max:255',
            'descripcion' => 'nullable|string',
        ]);

        return response()->json($this->service->update($id, $data));
    }

    public function destroy($id)
    {
        $this->service->delete($id);
        return response()->json(['message' => 'Grupo eliminado correctamente']);
    }

    public function addParticipante(Request $request, $id)
    {
        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'rol' => 'nullable|in:admin,miembro',
        ]);

        $rol = $data['rol'] ?? 'miembro';
        return response()->json($this->service->addParticipante($id, $data['user_id'], $rol));
    }

    public function removeParticipante(Request $request, $id)
    {
        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        return response()->json($this->service->removeParticipante($id, $data['user_id']));
    }

    public function balances($id)
    {
        return response()->json($this->service->calcularBalances($id));
    }
}
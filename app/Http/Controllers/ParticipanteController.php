<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\Interface\ParticipanteServiceInterface;

class ParticipanteController extends Controller
{
    protected $service;

    public function __construct(ParticipanteServiceInterface $service)
    {
        $this->service = $service;
    }

    public function index($grupoId)
    {
        return response()->json($this->service->all($grupoId));
    }

    public function show($id)
    {
        return response()->json($this->service->find($id));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'grupo_gasto_id' => 'required|exists:grupo_gastos,id',
            'nombre' => 'required|string|max:255',
            'email' => 'nullable|email',
            'user_id' => 'nullable|exists:users,id',
        ]);

        return response()->json($this->service->create($data), 201);
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'nombre' => 'string|max:255',
            'email' => 'nullable|email',
        ]);

        return response()->json($this->service->update($id, $data));
    }

    public function destroy($id)
    {
        $this->service->delete($id);
        return response()->json(['message' => 'Participante eliminado']);
    }

    public function vincularUsuario(Request $request, $id)
    {
        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        return response()->json($this->service->vincularUsuario($id, $data['user_id']));
    }
}
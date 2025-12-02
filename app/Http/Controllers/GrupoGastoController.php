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
            'participantes_externos' => 'nullable|array',
            'participantes_externos.*' => 'string|max:255',
        ]);

        $data['creador_id'] = $request->user()->id;

        return response()->json($this->service->create($data), 201);
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'nombre' => 'nullable|string|max:255',
            'descripcion' => 'nullable|string',
            'estado' => 'nullable|in:activo,inactivo,cerrado',
            'participantes_externos' => 'nullable|array',
            'participantes_externos.*' => 'string|max:255',
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
        ]);

        return response()->json($this->service->addParticipante($id, $data['user_id']));
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
        $balances = $this->service->calcularBalances($id);
        return response()->json($balances);
    }

    public function registrarPagoBalance(Request $request, $grupoId)
    {
        $data = $request->validate([
            'de_participante_id' => 'required|exists:participantes,id',
            'para_participante_id' => 'required|exists:participantes,id',
            'monto' => 'required|numeric|min:0.01',
        ]);

        try {
            $balances = $this->service->registrarPagoBalance($grupoId, $data);
            return response()->json($balances);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
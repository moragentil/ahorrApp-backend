<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\Interface\GastoCompartidoServiceInterface;

class GastoCompartidoController extends Controller
{
    protected $service;

    public function __construct(GastoCompartidoServiceInterface $service)
    {
        $this->service = $service;
    }

    public function index(Request $request, $grupoId)
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
            'descripcion' => 'required|string|max:255',
            'monto_total' => 'required|numeric|min:0',
            'pagado_por_participante_id' => 'required|exists:participantes,id',
            'fecha' => 'required|date',
            'participantes' => 'nullable|array',
            'participantes.*' => 'exists:participantes,id',
        ]);

        return response()->json($this->service->create($data), 201);
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'descripcion' => 'nullable|string|max:255',
            'monto_total' => 'nullable|numeric|min:0',
            'pagado_por_participante_id' => 'nullable|exists:participantes,id',
            'fecha' => 'nullable|date',
        ]);

        return response()->json($this->service->update($id, $data));
    }

    public function destroy($id)
    {
        $this->service->delete($id);
        return response()->json(['message' => 'Gasto compartido eliminado correctamente']);
    }

    public function registrarAportes(Request $request, $id)
    {
        $data = $request->validate([
            'aportes' => 'required|array',
            'aportes.*.participante_id' => 'required|exists:participantes,id',
            'aportes.*.monto_asignado' => 'required|numeric|min:0',
            'aportes.*.monto_pagado' => 'nullable|numeric|min:0',
        ]);

        return response()->json($this->service->registrarAportes($id, $data['aportes']));
    }
}
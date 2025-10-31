<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\Interface\AporteGastoServiceInterface;

class AporteGastoController extends Controller
{
    protected $service;

    public function __construct(AporteGastoServiceInterface $service)
    {
        $this->service = $service;
    }

    public function index($gastoCompartidoId)
    {
        return response()->json($this->service->all($gastoCompartidoId));
    }

    public function show($id)
    {
        return response()->json($this->service->find($id));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'gasto_compartido_id' => 'required|exists:gastos_compartidos,id',
            'participante_id' => 'required|exists:participantes,id',
            'monto_asignado' => 'required|numeric|min:0',
            'monto_pagado' => 'nullable|numeric|min:0',
        ]);

        return response()->json($this->service->create($data), 201);
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'monto_asignado' => 'nullable|numeric|min:0',
            'monto_pagado' => 'nullable|numeric|min:0',
        ]);

        return response()->json($this->service->update($id, $data));
    }

    public function destroy($id)
    {
        $this->service->delete($id);
        return response()->json(['message' => 'Aporte eliminado correctamente']);
    }

    public function registrarPago(Request $request, $id)
    {
        $data = $request->validate([
            'monto_pagado' => 'required|numeric|min:0',
        ]);

        return response()->json($this->service->registrarPago($id, $data['monto_pagado']));
    }
}
<?php

namespace App\Http\Controllers;

use App\Services\Interface\GastoCompartidoServiceInterface;
use Illuminate\Http\Request;

class GastoCompartidoController extends Controller
{
    protected $gastoCompartidoService;

    public function __construct(GastoCompartidoServiceInterface $gastoCompartidoService)
    {
        $this->gastoCompartidoService = $gastoCompartidoService;
    }

    public function index($grupoId)
    {
        $gastos = $this->gastoCompartidoService->all($grupoId);
        return response()->json($gastos);
    }

    public function show($id)
    {
        $gasto = $this->gastoCompartidoService->find($id);
        return response()->json($gasto);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'grupo_gasto_id' => 'required|exists:grupo_gastos,id',
            'pagado_por_participante_id' => 'required|exists:participantes,id',
            'descripcion' => 'required|string|max:255',
            'monto_total' => 'required|numeric|min:0',
            'fecha' => 'required|date',
            'participantes' => 'nullable|array',
            'participantes.*' => 'exists:participantes,id',
        ]);

        $gasto = $this->gastoCompartidoService->create($validated);
        return response()->json($gasto, 201);
    }

    public function update(Request $request, $id)
    {
        \Log::info('=== UPDATE GASTO COMPARTIDO ===');
        \Log::info('ID:', ['id' => $id]);
        \Log::info('Request data:', $request->all());
        
        $validated = $request->validate([
            'descripcion' => 'sometimes|string|max:255',
            'monto_total' => 'sometimes|numeric|min:0',
            'fecha' => 'sometimes|date',
            'pagado_por_participante_id' => 'sometimes|exists:participantes,id',
            'participantes' => 'sometimes|array',
            'participantes.*' => 'exists:participantes,id',
        ]);

        \Log::info('Validated data:', $validated);

        try {
            $gasto = $this->gastoCompartidoService->update($id, $validated);
            \Log::info('Gasto actualizado:', ['gasto' => $gasto]);
            return response()->json($gasto);
        } catch (\Exception $e) {
            \Log::error('Error actualizando gasto:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        $this->gastoCompartidoService->delete($id);
        return response()->json(['message' => 'Gasto eliminado correctamente']);
    }

    public function registrarAportes(Request $request, $id)
    {
        $validated = $request->validate([
            'aportes' => 'required|array',
            'aportes.*.participante_id' => 'required|exists:participantes,id',
            'aportes.*.monto_asignado' => 'required|numeric|min:0',
            'aportes.*.monto_pagado' => 'nullable|numeric|min:0',
        ]);

        $gasto = $this->gastoCompartidoService->registrarAportes($id, $validated['aportes']);
        return response()->json($gasto);
    }
}
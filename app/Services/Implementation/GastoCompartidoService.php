<?php

namespace App\Services\Implementation;

use App\Models\GastoCompartido;
use App\Models\AporteGasto;
use App\Services\Interface\GastoCompartidoServiceInterface;
use Illuminate\Support\Facades\DB;

class GastoCompartidoService implements GastoCompartidoServiceInterface
{
    public function all($grupoId)
    {
        return GastoCompartido::where('grupo_gasto_id', $grupoId)
            ->with(['pagador', 'aportes.usuario'])
            ->orderBy('fecha', 'desc')
            ->get();
    }

    public function find($id)
    {
        return GastoCompartido::with(['grupo', 'pagador', 'aportes.usuario'])
            ->findOrFail($id);
    }

    public function create(array $data)
    {
        DB::beginTransaction();
        try {
            $gasto = GastoCompartido::create($data);

            // Si se proporcionan participantes, crear aportes automáticamente
            if (isset($data['participantes']) && is_array($data['participantes'])) {
                $montoPorPersona = $data['monto_total'] / count($data['participantes']);
                
                foreach ($data['participantes'] as $userId) {
                    AporteGasto::create([
                        'gasto_compartido_id' => $gasto->id,
                        'user_id' => $userId,
                        'monto_esperado' => $montoPorPersona,
                        'monto_pagado' => 0,
                    ]);
                }
            }

            DB::commit();
            return $gasto->fresh(['pagador', 'aportes.usuario']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function update($id, array $data)
    {
        $gasto = GastoCompartido::findOrFail($id);
        $gasto->update($data);
        return $gasto->fresh(['pagador', 'aportes.usuario']);
    }

    public function delete($id)
    {
        $gasto = GastoCompartido::findOrFail($id);
        $gasto->delete();
        return true;
    }

    public function registrarAportes($gastoId, array $aportes)
    {
        DB::beginTransaction();
        try {
            foreach ($aportes as $aporte) {
                AporteGasto::updateOrCreate(
                    [
                        'gasto_compartido_id' => $gastoId,
                        'user_id' => $aporte['user_id'],
                    ],
                    [
                        'monto_esperado' => $aporte['monto_esperado'],
                        'monto_pagado' => $aporte['monto_pagado'] ?? 0,
                    ]
                );
            }

            DB::commit();
            return GastoCompartido::with('aportes.usuario')->findOrFail($gastoId);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
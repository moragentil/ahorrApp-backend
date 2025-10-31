<?php

namespace App\Services\Implementation;

use App\Models\GrupoGasto;
use App\Services\Interface\GrupoGastoServiceInterface;
use Illuminate\Support\Facades\DB;

class GrupoGastoService implements GrupoGastoServiceInterface
{
    public function all($userId)
    {
        return GrupoGasto::where('creado_por', $userId)
            ->orWhereHas('participantes', function($q) use ($userId) {
                $q->where('user_id', $userId);
            })
            ->with(['participantes', 'gastos', 'creador'])
            ->get();
    }

    public function find($id)
    {
        return GrupoGasto::with([
            'participantes', 
            'gastos.pagador', 
            'gastos.aportes.usuario',
            'creador'
        ])->findOrFail($id);
    }

    public function create(array $data)
    {
        DB::beginTransaction();
        try {
            $grupo = GrupoGasto::create($data);
            
            // Agregar al creador como participante con rol admin
            $grupo->participantes()->attach($data['creado_por'], ['rol' => 'admin']);
            
            DB::commit();
            return $grupo->fresh(['participantes', 'creador']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function update($id, array $data)
    {
        $grupo = GrupoGasto::findOrFail($id);
        $grupo->update($data);
        return $grupo->fresh(['participantes', 'gastos']);
    }

    public function delete($id)
    {
        $grupo = GrupoGasto::findOrFail($id);
        $grupo->delete();
        return true;
    }

    public function addParticipante($grupoId, $userId, $rol = 'miembro')
    {
        $grupo = GrupoGasto::findOrFail($grupoId);
        $grupo->participantes()->syncWithoutDetaching([$userId => ['rol' => $rol]]);
        return $grupo->fresh('participantes');
    }

    public function removeParticipante($grupoId, $userId)
    {
        $grupo = GrupoGasto::findOrFail($grupoId);
        $grupo->participantes()->detach($userId);
        return $grupo->fresh('participantes');
    }

    public function calcularBalances($grupoId)
    {
        $grupo = GrupoGasto::with([
            'participantes',
            'gastos.aportes'
        ])->findOrFail($grupoId);

        $balances = [];

        // Inicializar balances para cada participante
        foreach ($grupo->participantes as $participante) {
            $balances[$participante->id] = [
                'user' => $participante,
                'total_pagado' => 0,
                'total_debe' => 0,
                'balance' => 0,
                'deudas' => [], // A quién le debe y cuánto
                'creditos' => [], // Quién le debe y cuánto
            ];
        }

        // Calcular totales por cada gasto
        foreach ($grupo->gastos as $gasto) {
            $pagadorId = $gasto->pagado_por;
            
            // Sumar lo que pagó el pagador
            if (isset($balances[$pagadorId])) {
                $balances[$pagadorId]['total_pagado'] += $gasto->monto_total;
            }

            // Sumar lo que debe cada participante según sus aportes
            foreach ($gasto->aportes as $aporte) {
                $userId = $aporte->user_id;
                if (isset($balances[$userId])) {
                    $balances[$userId]['total_debe'] += $aporte->monto_esperado;
                }
            }
        }

        // Calcular balance neto (lo que pagó - lo que debe)
        foreach ($balances as $userId => &$balance) {
            $balance['balance'] = $balance['total_pagado'] - $balance['total_debe'];
        }

        // Calcular deudas y créditos específicos
        $deudores = array_filter($balances, fn($b) => $b['balance'] < 0);
        $acreedores = array_filter($balances, fn($b) => $b['balance'] > 0);

        foreach ($deudores as $deudorId => $deudor) {
            $montoRestante = abs($deudor['balance']);
            
            foreach ($acreedores as $acreedorId => $acreedor) {
                if ($montoRestante <= 0.01) break;
                if ($acreedor['balance'] <= 0.01) continue;

                $montoPago = min($montoRestante, $acreedor['balance']);
                
                // Registrar la deuda
                $balances[$deudorId]['deudas'][] = [
                    'acreedor' => $acreedor['user'],
                    'monto' => round($montoPago, 2),
                ];

                // Registrar el crédito
                $balances[$acreedorId]['creditos'][] = [
                    'deudor' => $deudor['user'],
                    'monto' => round($montoPago, 2),
                ];

                $montoRestante -= $montoPago;
                $balances[$acreedorId]['balance'] -= $montoPago;
            }
        }

        return array_values($balances);
    }
}
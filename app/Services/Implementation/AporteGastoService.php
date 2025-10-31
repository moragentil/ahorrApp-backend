<?php

namespace App\Services\Implementation;

use App\Models\AporteGasto;
use App\Models\Participante;
use App\Services\Interface\AporteGastoServiceInterface;
use Illuminate\Support\Facades\DB;

class AporteGastoService implements AporteGastoServiceInterface
{
    public function all($gastoCompartidoId)
    {
        return AporteGasto::where('gasto_compartido_id', $gastoCompartidoId)
            ->with('participante.usuario')
            ->get()
            ->map(function($aporte) {
                return [
                    'id' => $aporte->id,
                    'gasto_compartido_id' => $aporte->gasto_compartido_id,
                    'participante_id' => $aporte->participante_id,
                    'participante' => [
                        'id' => $aporte->participante->id,
                        'nombre' => $aporte->participante->nombre,
                        'email' => $aporte->participante->email,
                        'es_usuario' => !is_null($aporte->participante->user_id),
                    ],
                    'monto_asignado' => $aporte->monto_asignado,
                    'monto_pagado' => $aporte->monto_pagado,
                    'saldo' => $aporte->saldo,
                    'estado' => $aporte->estado,
                    'created_at' => $aporte->created_at,
                    'updated_at' => $aporte->updated_at,
                ];
            });
    }

    public function find($id)
    {
        $aporte = AporteGasto::with([
            'participante.usuario',
            'gastoCompartido.grupo',
            'gastoCompartido.pagador'
        ])->findOrFail($id);

        return [
            'id' => $aporte->id,
            'gasto_compartido_id' => $aporte->gasto_compartido_id,
            'participante_id' => $aporte->participante_id,
            'participante' => [
                'id' => $aporte->participante->id,
                'nombre' => $aporte->participante->nombre,
                'email' => $aporte->participante->email,
                'es_usuario' => !is_null($aporte->participante->user_id),
            ],
            'monto_asignado' => $aporte->monto_asignado,
            'monto_pagado' => $aporte->monto_pagado,
            'saldo' => $aporte->saldo,
            'estado' => $aporte->estado,
            'gasto_compartido' => [
                'id' => $aporte->gastoCompartido->id,
                'descripcion' => $aporte->gastoCompartido->descripcion,
                'monto_total' => $aporte->gastoCompartido->monto_total,
                'fecha' => $aporte->gastoCompartido->fecha->format('Y-m-d'),
                'pagador' => [
                    'id' => $aporte->gastoCompartido->pagador->id,
                    'nombre' => $aporte->gastoCompartido->pagador->nombre,
                ],
            ],
            'created_at' => $aporte->created_at,
            'updated_at' => $aporte->updated_at,
        ];
    }

    public function create(array $data)
    {
        // Validar que el participante pertenezca al grupo del gasto
        $participante = Participante::findOrFail($data['participante_id']);
        $gastoCompartido = \App\Models\GastoCompartido::findOrFail($data['gasto_compartido_id']);
        
        if ($participante->grupo_gasto_id !== $gastoCompartido->grupo_gasto_id) {
            throw new \Exception('El participante no pertenece al grupo del gasto');
        }

        // Determinar estado inicial
        $montoPagado = $data['monto_pagado'] ?? 0;
        $estado = 'pendiente';
        
        if ($montoPagado >= $data['monto_asignado']) {
            $estado = 'pagado';
        }

        $aporte = AporteGasto::create([
            'gasto_compartido_id' => $data['gasto_compartido_id'],
            'participante_id' => $data['participante_id'],
            'monto_asignado' => $data['monto_asignado'],
            'monto_pagado' => $montoPagado,
            'estado' => $estado,
        ]);

        return $this->find($aporte->id);
    }

    public function update($id, array $data)
    {
        DB::beginTransaction();
        try {
            $aporte = AporteGasto::findOrFail($id);
            
            $updateData = [];
            
            if (isset($data['monto_asignado'])) {
                $updateData['monto_asignado'] = $data['monto_asignado'];
            }
            
            if (isset($data['monto_pagado'])) {
                $updateData['monto_pagado'] = $data['monto_pagado'];
            }
            
            // Actualizar estado según los montos
            $montoAsignado = $data['monto_asignado'] ?? $aporte->monto_asignado;
            $montoPagado = $data['monto_pagado'] ?? $aporte->monto_pagado;
            
            if ($montoPagado >= $montoAsignado) {
                $updateData['estado'] = 'pagado';
            } else {
                $updateData['estado'] = 'pendiente';
            }
            
            $aporte->update($updateData);

            DB::commit();
            return $this->find($id);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function delete($id)
    {
        $aporte = AporteGasto::findOrFail($id);
        $aporte->delete();
        return true;
    }

    public function registrarPago($id, $montoPagado)
    {
        DB::beginTransaction();
        try {
            $aporte = AporteGasto::findOrFail($id);
            
            $nuevoMontoPagado = $aporte->monto_pagado + $montoPagado;
            
            // Determinar nuevo estado
            $estado = 'pendiente';
            if ($nuevoMontoPagado >= $aporte->monto_asignado) {
                $estado = 'pagado';
            }
            
            $aporte->update([
                'monto_pagado' => $nuevoMontoPagado,
                'estado' => $estado,
            ]);

            DB::commit();
            return $this->find($id);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
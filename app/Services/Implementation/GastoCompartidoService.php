<?php

namespace App\Services\Implementation;

use App\Models\GastoCompartido;
use App\Models\AporteGasto;
use App\Models\Participante;
use App\Services\Interface\GastoCompartidoServiceInterface;
use Illuminate\Support\Facades\DB;

class GastoCompartidoService implements GastoCompartidoServiceInterface
{
    public function all($grupoId)
    {
        return GastoCompartido::where('grupo_gasto_id', $grupoId)
            ->with(['pagador.usuario', 'aportes.participante.usuario'])
            ->orderBy('fecha', 'desc')
            ->get()
            ->map(function($gasto) {
                return [
                    'id' => $gasto->id,
                    'descripcion' => $gasto->descripcion,
                    'monto_total' => $gasto->monto_total,
                    'fecha' => $gasto->fecha->format('Y-m-d'),
                    'pagador' => [
                        'id' => $gasto->pagador->id,
                        'nombre' => $gasto->pagador->nombre,
                        'email' => $gasto->pagador->email,
                        'es_usuario' => !is_null($gasto->pagador->user_id),
                    ],
                    'aportes' => $gasto->aportes->map(function($aporte) {
                        return [
                            'id' => $aporte->id,
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
                        ];
                    }),
                    'created_at' => $gasto->created_at,
                    'updated_at' => $gasto->updated_at,
                ];
            });
    }

    public function find($id)
    {
        $gasto = GastoCompartido::with([
            'grupo',
            'pagador.usuario',
            'aportes.participante.usuario'
        ])->findOrFail($id);

        return [
            'id' => $gasto->id,
            'grupo_gasto_id' => $gasto->grupo_gasto_id,
            'descripcion' => $gasto->descripcion,
            'monto_total' => $gasto->monto_total,
            'fecha' => $gasto->fecha->format('Y-m-d'),
            'pagador' => [
                'id' => $gasto->pagador->id,
                'nombre' => $gasto->pagador->nombre,
                'email' => $gasto->pagador->email,
                'es_usuario' => !is_null($gasto->pagador->user_id),
            ],
            'aportes' => $gasto->aportes->map(function($aporte) {
                return [
                    'id' => $aporte->id,
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
                ];
            }),
            'grupo' => $gasto->grupo,
            'created_at' => $gasto->created_at,
            'updated_at' => $gasto->updated_at,
        ];
    }

    public function create(array $data)
    {
        DB::beginTransaction();
        try {
            // Validar que el pagador sea un participante del grupo
            $pagador = Participante::where('id', $data['pagado_por_participante_id'])
                ->where('grupo_gasto_id', $data['grupo_gasto_id'])
                ->firstOrFail();

            $gasto = GastoCompartido::create([
                'grupo_gasto_id' => $data['grupo_gasto_id'],
                'pagado_por_participante_id' => $data['pagado_por_participante_id'],
                'descripcion' => $data['descripcion'],
                'monto_total' => $data['monto_total'],
                'fecha' => $data['fecha'],
            ]);

            // Si se proporcionan participantes, crear aportes automáticamente
            if (isset($data['participantes']) && is_array($data['participantes']) && count($data['participantes']) > 0) {
                $montoPorPersona = round($data['monto_total'] / count($data['participantes']), 2);
                
                foreach ($data['participantes'] as $participanteId) {
                    // Validar que el participante pertenezca al grupo
                    $participante = Participante::where('id', $participanteId)
                        ->where('grupo_gasto_id', $data['grupo_gasto_id'])
                        ->firstOrFail();

                    AporteGasto::create([
                        'gasto_compartido_id' => $gasto->id,
                        'participante_id' => $participanteId,
                        'monto_asignado' => $montoPorPersona,
                        'monto_pagado' => 0,
                        'estado' => 'pendiente',
                    ]);
                }
            }

            DB::commit();
            return $this->find($gasto->id);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function update($id, array $data)
    {
        DB::beginTransaction();
        try {
            $gasto = GastoCompartido::findOrFail($id);
            
            $updateData = [];
            
            if (isset($data['descripcion'])) {
                $updateData['descripcion'] = $data['descripcion'];
            }
            
            if (isset($data['monto_total'])) {
                $updateData['monto_total'] = $data['monto_total'];
                
                // Recalcular aportes si cambió el monto
                $aportes = $gasto->aportes;
                if ($aportes->count() > 0) {
                    $montoPorPersona = round($data['monto_total'] / $aportes->count(), 2);
                    foreach ($aportes as $aporte) {
                        $aporte->update(['monto_asignado' => $montoPorPersona]);
                    }
                }
            }
            
            if (isset($data['pagado_por_participante_id'])) {
                // Validar que el participante pertenezca al grupo
                Participante::where('id', $data['pagado_por_participante_id'])
                    ->where('grupo_gasto_id', $gasto->grupo_gasto_id)
                    ->firstOrFail();
                    
                $updateData['pagado_por_participante_id'] = $data['pagado_por_participante_id'];
            }
            
            if (isset($data['fecha'])) {
                $updateData['fecha'] = $data['fecha'];
            }

            $gasto->update($updateData);

            DB::commit();
            return $this->find($id);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
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
            $gasto = GastoCompartido::findOrFail($gastoId);

            foreach ($aportes as $aporteData) {
                // Validar que el participante pertenezca al grupo
                Participante::where('id', $aporteData['participante_id'])
                    ->where('grupo_gasto_id', $gasto->grupo_gasto_id)
                    ->firstOrFail();

                $estado = 'pendiente';
                $montoPagado = $aporteData['monto_pagado'] ?? 0;
                
                if ($montoPagado >= $aporteData['monto_asignado']) {
                    $estado = 'pagado';
                }

                AporteGasto::updateOrCreate(
                    [
                        'gasto_compartido_id' => $gastoId,
                        'participante_id' => $aporteData['participante_id'],
                    ],
                    [
                        'monto_asignado' => $aporteData['monto_asignado'],
                        'monto_pagado' => $montoPagado,
                        'estado' => $estado,
                    ]
                );
            }

            DB::commit();
            return $this->find($gastoId);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
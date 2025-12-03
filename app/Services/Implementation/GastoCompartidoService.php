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
            ->soloGastos() // ✅ Excluir pagos de balance
            ->with([
                'pagador.usuario',
                'aportes.participante.usuario'
            ])
            ->orderBy('fecha', 'desc')
            ->get()
            ->map(function($gasto) {
                return [
                    'id' => $gasto->id,
                    'grupo_gasto_id' => $gasto->grupo_gasto_id,
                    'descripcion' => $gasto->descripcion,
                    'icono' => $gasto->icono,
                    'monto_total' => $gasto->monto_total,
                    'fecha' => $gasto->fecha->format('Y-m-d'),
                    'pagador' => $gasto->pagador,
                    'aportes' => $gasto->aportes,
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
                $pagadorId = $data['pagado_por_participante_id'];
                
                foreach ($data['participantes'] as $participanteId) {
                    // Validar que el participante pertenzca al grupo
                    $participante = Participante::where('id', $participanteId)
                        ->where('grupo_gasto_id', $data['grupo_gasto_id'])
                        ->firstOrFail();

                    // Determinar si este participante es quien pagó
                    $esPagador = ($participanteId == $pagadorId);
                    $montoPagado = $esPagador ? $montoPorPersona : 0;
                    $estado = $esPagador ? 'pagado' : 'pendiente';

                    AporteGasto::create([
                        'gasto_compartido_id' => $gasto->id,
                        'participante_id' => $participanteId,
                        'monto_asignado' => $montoPorPersona,
                        'monto_pagado' => $montoPagado,
                        'estado' => $estado,
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
        
        if (isset($data['pagado_por_participante_id'])) {
            // Validar que el nuevo pagador sea un participante del grupo
            $pagador = Participante::where('id', $data['pagado_por_participante_id'])
                ->where('grupo_gasto_id', $gasto->grupo_gasto_id)
                ->firstOrFail();
                
            $updateData['pagado_por_participante_id'] = $data['pagado_por_participante_id'];
        }
        
        if (isset($data['fecha'])) {
            $updateData['fecha'] = $data['fecha'];
        }

        // Si se actualiza el monto o los participantes, recalcular aportes
        $recalcularAportes = false;
        $montoTotal = $gasto->monto_total;
        
        if (isset($data['monto_total'])) {
            $updateData['monto_total'] = $data['monto_total'];
            $montoTotal = $data['monto_total'];
            $recalcularAportes = true;
        }

        // Actualizar el gasto
        $gasto->update($updateData);

        // Si se proporcionan nuevos participantes, actualizar aportes
        if (isset($data['participantes']) && is_array($data['participantes']) && count($data['participantes']) > 0) {
            // Eliminar aportes existentes
            AporteGasto::where('gasto_compartido_id', $id)->delete();
            
            // Crear nuevos aportes
            $montoPorPersona = round($montoTotal / count($data['participantes']), 2);
            $pagadorId = $updateData['pagado_por_participante_id'] ?? $gasto->pagado_por_participante_id;
            
            foreach ($data['participantes'] as $participanteId) {
                // Asegurar que el ID sea un entero
                $participanteId = (int) $participanteId;
                
                // Validar que el participante pertenezca al grupo
                $participante = Participante::where('id', $participanteId)
                    ->where('grupo_gasto_id', $gasto->grupo_gasto_id)
                    ->firstOrFail();

                $esPagador = ($participanteId == $pagadorId);
                $montoPagado = $esPagador ? $montoPorPersona : 0;
                $estado = $esPagador ? 'pagado' : 'pendiente';

                AporteGasto::create([
                    'gasto_compartido_id' => $gasto->id,
                    'participante_id' => $participanteId,
                    'monto_asignado' => $montoPorPersona,
                    'monto_pagado' => $montoPagado,
                    'estado' => $estado,
                ]);
            }
            
            // Marcar para no recalcular dos veces
            $recalcularAportes = false;
        }

        // Solo actualizar montos si cambió el monto_total pero no los participantes
        if ($recalcularAportes) {
            $aportes = AporteGasto::where('gasto_compartido_id', $id)->get();
            if ($aportes->count() > 0) {
                $montoPorPersona = round($montoTotal / $aportes->count(), 2);
                $pagadorId = $updateData['pagado_por_participante_id'] ?? $gasto->pagado_por_participante_id;
                
                foreach ($aportes as $aporte) {
                    $esPagador = ($aporte->participante_id == $pagadorId);
                    $montoPagado = $esPagador ? $montoPorPersona : min($aporte->monto_pagado, $montoPorPersona);
                    $estado = ($montoPagado >= $montoPorPersona) ? 'pagado' : 'pendiente';
                    
                    $aporte->update([
                        'monto_asignado' => $montoPorPersona,
                        'monto_pagado' => $montoPagado,
                        'estado' => $estado,
                    ]);
                }
            }
        }

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
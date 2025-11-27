<?php

namespace App\Services\Implementation;

use App\Models\GrupoGasto;
use App\Models\Participante;
use App\Services\Interface\GrupoGastoServiceInterface;
use Illuminate\Support\Facades\DB;

class GrupoGastoService implements GrupoGastoServiceInterface
{
    public function all($userId)
    {
        return GrupoGasto::where('creador_id', $userId)
            ->orWhereHas('miembros', function($q) use ($userId) {
                $q->where('user_id', $userId);
            })
            ->with(['miembros', 'participantes.usuario', 'creador', 'gastosCompartidos'])
            ->get()
            ->map(function($grupo) {
                return [
                    'id' => $grupo->id,
                    'nombre' => $grupo->nombre,
                    'descripcion' => $grupo->descripcion,
                    'estado' => $grupo->estado,
                    'creador_id' => $grupo->creador_id,
                    'creador' => $grupo->creador,
                    'miembros' => $grupo->miembros,
                    'participantes' => $grupo->participantes,
                    'total_gastos' => $grupo->gastosCompartidos->count(),
                    'monto_total' => $grupo->gastosCompartidos->sum('monto_total'),
                    'created_at' => $grupo->created_at,
                    'updated_at' => $grupo->updated_at,
                ];
            });
    }

    public function find($id)
    {
        $grupo = GrupoGasto::with([
            'miembros',
            'participantes.usuario',
            'gastosCompartidos.pagador',
            'gastosCompartidos.aportes.participante.usuario',
            'creador',
            'invitaciones' => function($q) {
                $q->where('estado', 'pendiente');
            }
        ])->findOrFail($id);

        return [
            'id' => $grupo->id,
            'nombre' => $grupo->nombre,
            'descripcion' => $grupo->descripcion,
            'estado' => $grupo->estado,
            'creador_id' => $grupo->creador_id,
            'creador' => $grupo->creador,
            'miembros' => $grupo->miembros,
            'participantes' => $grupo->participantes,
            'gastos_compartidos' => $grupo->gastosCompartidos,
            'invitaciones_pendientes' => $grupo->invitaciones,
            'created_at' => $grupo->created_at,
            'updated_at' => $grupo->updated_at,
        ];
    }

    public function create(array $data)
    {
        DB::beginTransaction();
        try {
            // Crear el grupo
            $grupo = GrupoGasto::create([
                'nombre' => $data['nombre'],
                'descripcion' => $data['descripcion'] ?? null,
                'creador_id' => $data['creador_id'],
                'estado' => 'activo',
            ]);
            
            // Agregar al creador como miembro (puede ver/gestionar el grupo)
            $grupo->miembros()->attach($data['creador_id']);

            // Crear participante para el creador (aparece en gastos)
            $creador = \App\Models\User::findOrFail($data['creador_id']);
            Participante::create([
                'grupo_gasto_id' => $grupo->id,
                'nombre' => $creador->name,
                'email' => $creador->email,
                'user_id' => $creador->id,
            ]);

            // Agregar participantes externos si existen
            if (isset($data['participantes_externos']) && is_array($data['participantes_externos'])) {
                foreach ($data['participantes_externos'] as $nombreParticipante) {
                    if (!empty(trim($nombreParticipante))) {
                        Participante::create([
                            'grupo_gasto_id' => $grupo->id,
                            'nombre' => trim($nombreParticipante),
                            'email' => null,
                            'user_id' => null,
                        ]);
                    }
                }
            }
            
            DB::commit();
            return $this->find($grupo->id);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function update($id, array $data)
    {
        DB::beginTransaction();
        try {
            $grupo = GrupoGasto::findOrFail($id);
            
            $grupo->update([
                'nombre' => $data['nombre'] ?? $grupo->nombre,
                'descripcion' => $data['descripcion'] ?? $grupo->descripcion,
                'estado' => $data['estado'] ?? $grupo->estado,
            ]);

            // Actualizar participantes externos si se proporcionan
            if (isset($data['participantes_externos']) && is_array($data['participantes_externos'])) {
                // Eliminar participantes externos actuales (sin user_id)
                Participante::where('grupo_gasto_id', $id)
                    ->whereNull('user_id')
                    ->delete();

                // Crear nuevos participantes externos
                foreach ($data['participantes_externos'] as $nombreParticipante) {
                    if (!empty(trim($nombreParticipante))) {
                        Participante::create([
                            'grupo_gasto_id' => $id,
                            'nombre' => trim($nombreParticipante),
                            'email' => null,
                            'user_id' => null,
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
        $grupo = GrupoGasto::findOrFail($id);
        $grupo->delete();
        return true;
    }

    public function addParticipante($grupoId, $userId, $rol = 'miembro')
    {
        DB::beginTransaction();
        try {
            $grupo = GrupoGasto::findOrFail($grupoId);
            $usuario = \App\Models\User::findOrFail($userId);

            // Agregar como miembro (puede ver/gestionar el grupo)
            $grupo->miembros()->syncWithoutDetaching([$userId]);

            // Verificar si ya existe como participante
            $participanteExistente = Participante::where('grupo_gasto_id', $grupoId)
                ->where('user_id', $userId)
                ->first();

            if (!$participanteExistente) {
                // Crear participante (aparece en gastos)
                Participante::create([
                    'grupo_gasto_id' => $grupoId,
                    'nombre' => $usuario->name,
                    'email' => $usuario->email,
                    'user_id' => $userId,
                ]);
            }

            DB::commit();
            return $this->find($grupoId);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function removeParticipante($grupoId, $userId)
    {
        DB::beginTransaction();
        try {
            $grupo = GrupoGasto::findOrFail($grupoId);
            
            // Remover como miembro
            $grupo->miembros()->detach($userId);

            // Remover como participante
            Participante::where('grupo_gasto_id', $grupoId)
                ->where('user_id', $userId)
                ->delete();

            DB::commit();
            return $this->find($grupoId);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function calcularBalances($grupoId)
    {
        $grupo = GrupoGasto::with([
            'participantes.usuario',
            'gastosCompartidos.aportes.participante'
        ])->findOrFail($grupoId);

        $balances = [];

        // Inicializar balances para cada participante
        foreach ($grupo->participantes as $participante) {
            $balances[$participante->id] = [
                'participante_id' => $participante->id,
                'nombre' => $participante->nombre,
                'email' => $participante->email,
                'es_usuario' => !is_null($participante->user_id),
                'total_pagado' => 0,
                'total_debe' => 0,
                'balance' => 0,
            ];
        }

        // Calcular totales por cada gasto
        foreach ($grupo->gastosCompartidos as $gasto) {
            $pagadorId = $gasto->pagado_por_participante_id;
            
            // Sumar lo que pagó el pagador
            if (isset($balances[$pagadorId])) {
                $balances[$pagadorId]['total_pagado'] += (float) $gasto->monto_total;
            }

            // Sumar lo que debe cada participante según sus aportes
            foreach ($gasto->aportes as $aporte) {
                $participanteId = $aporte->participante_id;
                if (isset($balances[$participanteId])) {
                    $balances[$participanteId]['total_debe'] += (float) $aporte->monto_asignado;
                }
            }
        }

        // Calcular balance neto (lo que pagó - lo que debe)
        foreach ($balances as &$balance) {
            $balance['balance'] = round($balance['total_pagado'] - $balance['total_debe'], 2);
            $balance['total_pagado'] = round($balance['total_pagado'], 2);
            $balance['total_debe'] = round($balance['total_debe'], 2);
        }

        // Calcular transacciones de equilibrio
        $transacciones = $this->calcularTransacciones($balances);

        return [
            'balances' => array_values($balances),
            'transacciones' => $transacciones,
        ];
    }

    private function calcularTransacciones($balances)
    {
        $deudores = [];
        $acreedores = [];
        
        // Separar en deudores y acreedores
        foreach ($balances as $balance) {
            if ($balance['balance'] < 0) {
                $deudores[] = [
                    'participante_id' => $balance['participante_id'],
                    'nombre' => $balance['nombre'],
                    'monto' => abs($balance['balance'])
                ];
            } elseif ($balance['balance'] > 0) {
                $acreedores[] = [
                    'participante_id' => $balance['participante_id'],
                    'nombre' => $balance['nombre'],
                    'monto' => $balance['balance']
                ];
            }
        }

        $transacciones = [];
        $i = 0;
        $j = 0;

        // Algoritmo greedy para minimizar transacciones
        while ($i < count($deudores) && $j < count($acreedores)) {
            $montoTransferencia = min($deudores[$i]['monto'], $acreedores[$j]['monto']);
            
            if ($montoTransferencia > 0.01) { // Ignorar diferencias muy pequeñas
                $transacciones[] = [
                    'de_participante_id' => $deudores[$i]['participante_id'],
                    'de_nombre' => $deudores[$i]['nombre'],
                    'para_participante_id' => $acreedores[$j]['participante_id'],
                    'para_nombre' => $acreedores[$j]['nombre'],
                    'monto' => round($montoTransferencia, 2),
                ];
            }

            $deudores[$i]['monto'] -= $montoTransferencia;
            $acreedores[$j]['monto'] -= $montoTransferencia;

            if ($deudores[$i]['monto'] < 0.01) {
                $i++;
            }
            if ($acreedores[$j]['monto'] < 0.01) {
                $j++;
            }
        }

        return $transacciones;
    }
}
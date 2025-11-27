<?php

namespace App\Services\Implementation;

use App\Models\InvitacionGrupo;
use App\Models\GrupoGasto;
use App\Models\User;
use App\Models\Participante;
use App\Services\Interface\InvitacionGrupoServiceInterface;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class InvitacionGrupoService implements InvitacionGrupoServiceInterface
{
    public function enviarInvitacion($grupoId, $email, $invitadoPor)
    {
        $grupo = GrupoGasto::findOrFail($grupoId);

        // Buscar si existe un usuario con ese email
        $usuario = User::where('email', $email)->first();

        // Si existe usuario, verificar si ya es miembro
        if ($usuario) {
            $esMiembro = $grupo->miembros()->where('user_id', $usuario->id)->exists();
            if ($esMiembro) {
                throw new \Exception('El usuario ya es miembro del grupo');
            }
        }

        // Verificar si ya existe una invitación pendiente para este email/usuario
        $queryInvitacion = InvitacionGrupo::where('grupo_gasto_id', $grupoId)
            ->where('estado', 'pendiente');

        if ($usuario) {
            $queryInvitacion->where('user_id', $usuario->id);
        } else {
            $queryInvitacion->where('email', $email);
        }

        $invitacionExistente = $queryInvitacion->first();

        if ($invitacionExistente) {
            throw new \Exception('Ya existe una invitación pendiente para este usuario');
        }

        // Buscar participante sin usuario que coincida con el email
        $participanteAsociable = null;
        if ($usuario) {
            $participanteAsociable = Participante::where('grupo_gasto_id', $grupoId)
                ->where(function($query) use ($email, $usuario) {
                    $query->where('email', $email)
                          ->orWhere('nombre', $usuario->name);
                })
                ->whereNull('user_id')
                ->first();
        }

        // Crear invitación
        $invitacion = InvitacionGrupo::create([
            'grupo_gasto_id' => $grupoId,
            'email' => $email,
            'user_id' => $usuario ? $usuario->id : null,
            'invitado_por' => $invitadoPor,
            'token' => Str::random(64),
            'expira_en' => now()->addDays(7),
            'estado' => 'pendiente',
        ]);

        $result = $invitacion->fresh(['grupo', 'invitador', 'usuario']);
        
        // Agregar información sobre participante asociable si existe
        if ($participanteAsociable) {
            $result->participante_asociable = [
                'id' => $participanteAsociable->id,
                'nombre' => $participanteAsociable->nombre,
                'total_gastos' => $participanteAsociable->aportes()->count(),
            ];
        }

        return $result;
    }

    public function misInvitaciones($userId)
    {
        $user = User::findOrFail($userId);
        
        return InvitacionGrupo::where(function($query) use ($user, $userId) {
                $query->where('user_id', $userId)
                      ->orWhere('email', $user->email);
            })
            ->where('estado', 'pendiente')
            ->where(function($query) {
                $query->whereNull('expira_en')
                      ->orWhere('expira_en', '>', now());
            })
            ->with(['grupo.participantes', 'invitador'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function aceptarInvitacion($token, $userId)
    {
        DB::beginTransaction();
        try {
            $user = User::findOrFail($userId);
            
            $invitacion = InvitacionGrupo::where('token', $token)
                ->where(function($query) use ($user, $userId) {
                    $query->where('user_id', $userId)
                          ->orWhere('email', $user->email);
                })
                ->where('estado', 'pendiente')
                ->firstOrFail();

            if ($invitacion->estaExpirada()) {
                throw new \Exception('La invitación ha expirado');
            }

            $grupo = GrupoGasto::findOrFail($invitacion->grupo_gasto_id);
            
            // Verificar que no sea ya miembro
            $esMiembro = $grupo->miembros()->where('user_id', $userId)->exists();
            if ($esMiembro) {
                throw new \Exception('Ya eres miembro del grupo');
            }

            // 1. Agregar como miembro (puede ver/gestionar el grupo)
            $grupo->miembros()->attach($userId);

            // 2. Buscar si existe un participante con el mismo email o nombre
            $participanteExistente = Participante::where('grupo_gasto_id', $grupo->id)
                ->where(function($query) use ($user) {
                    $query->where('email', $user->email)
                          ->orWhere('nombre', $user->name);
                })
                ->whereNull('user_id')
                ->first();

            if ($participanteExistente) {
                // Vincular el participante existente con el usuario
                // Esto preserva todos los gastos y aportes ya asociados
                $participanteExistente->update([
                    'user_id' => $userId,
                    'nombre' => $user->name, // Actualizar nombre por si cambió
                    'email' => $user->email, // Actualizar email
                ]);
            } else {
                // No existe participante previo, crear uno nuevo
                Participante::create([
                    'grupo_gasto_id' => $grupo->id,
                    'nombre' => $user->name,
                    'email' => $user->email,
                    'user_id' => $userId,
                ]);
            }

            // 3. Actualizar invitación
            $invitacion->update([
                'estado' => 'aceptada',
                'user_id' => $userId,
            ]);

            DB::commit();
            
            return $grupo->fresh(['miembros', 'participantes.usuario']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function rechazarInvitacion($token, $userId)
    {
        $user = User::findOrFail($userId);
        
        $invitacion = InvitacionGrupo::where('token', $token)
            ->where(function($query) use ($user, $userId) {
                $query->where('user_id', $userId)
                      ->orWhere('email', $user->email);
            })
            ->where('estado', 'pendiente')
            ->firstOrFail();

        $invitacion->update(['estado' => 'rechazada']);

        return $invitacion;
    }

    public function invitacionesPendientes($grupoId)
    {
        return InvitacionGrupo::where('grupo_gasto_id', $grupoId)
            ->where('estado', 'pendiente')
            ->with(['invitador', 'usuario'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function($invitacion) {
                return [
                    'id' => $invitacion->id,
                    'email' => $invitacion->email,
                    'token' => $invitacion->token,
                    'estado' => $invitacion->estado,
                    'expira_en' => $invitacion->expira_en,
                    'invitador' => [
                        'id' => $invitacion->invitador->id,
                        'name' => $invitacion->invitador->name,
                        'email' => $invitacion->invitador->email,
                    ],
                    'usuario' => $invitacion->usuario ? [
                        'id' => $invitacion->usuario->id,
                        'name' => $invitacion->usuario->name,
                        'email' => $invitacion->usuario->email,
                    ] : null,
                    'created_at' => $invitacion->created_at,
                ];
            });
    }

    public function cancelarInvitacion($invitacionId)
    {
        $invitacion = InvitacionGrupo::findOrFail($invitacionId);
        $invitacion->delete();
        return true;
    }
}
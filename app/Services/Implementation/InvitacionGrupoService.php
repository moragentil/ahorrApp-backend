<?php

namespace App\Services\Implementation;

use App\Models\InvitacionGrupo;
use App\Models\GrupoGasto;
use App\Models\User;
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
            $esMiembro = $grupo->participantes()->where('user_id', $usuario->id)->exists();
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

        return $invitacion->fresh(['grupo', 'invitador', 'usuario']);
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
            ->with(['grupo', 'invitador'])
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

            // Agregar al grupo
            $grupo = GrupoGasto::findOrFail($invitacion->grupo_gasto_id);
            
            // Verificar que no sea ya miembro
            $esMiembro = $grupo->participantes()->where('user_id', $userId)->exists();
            if ($esMiembro) {
                throw new \Exception('Ya eres miembro del grupo');
            }

            $grupo->participantes()->attach($userId, ['rol' => 'miembro']);

            // Actualizar invitación
            $invitacion->update([
                'estado' => 'aceptada',
                'user_id' => $userId, // Asegurarse que tenga el user_id
            ]);

            DB::commit();
            return $grupo->fresh(['participantes']);
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
            ->get();
    }

    public function cancelarInvitacion($invitacionId)
    {
        $invitacion = InvitacionGrupo::findOrFail($invitacionId);
        $invitacion->delete();
        return true;
    }
}
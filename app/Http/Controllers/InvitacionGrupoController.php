<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\Interface\InvitacionGrupoServiceInterface;

class InvitacionGrupoController extends Controller
{
    protected $service;

    public function __construct(InvitacionGrupoServiceInterface $service)
    {
        $this->service = $service;
    }

    public function enviarInvitacion(Request $request, $grupoId)
    {
        $data = $request->validate([
            'email' => 'required|email',
        ]);

        try {
            $invitacion = $this->service->enviarInvitacion(
                $grupoId,
                $data['email'],
                $request->user()->id
            );

            return response()->json([
                'message' => 'Invitación enviada correctamente',
                'invitacion' => $invitacion,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function misInvitaciones(Request $request)
    {
        $invitaciones = $this->service->misInvitaciones($request->user()->id);
        return response()->json($invitaciones);
    }

    public function aceptar(Request $request, $token)
    {
        try {
            $grupo = $this->service->aceptarInvitacion($token, $request->user()->id);
            return response()->json([
                'message' => 'Te has unido al grupo correctamente',
                'grupo' => $grupo,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function rechazar(Request $request, $token)
    {
        try {
            $this->service->rechazarInvitacion($token, $request->user()->id);
            return response()->json([
                'message' => 'Invitación rechazada',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function pendientes($grupoId)
    {
        $invitaciones = $this->service->invitacionesPendientes($grupoId);
        return response()->json($invitaciones);
    }

    public function cancelar($invitacionId)
    {
        $this->service->cancelarInvitacion($invitacionId);
        return response()->json([
            'message' => 'Invitación cancelada',
        ]);
    }

    public function generarEnlace(Request $request, $grupoId)
    {
        try {
            $invitacion = $this->service->generarEnlaceInvitacion($grupoId, auth()->id());
            
            return response()->json([
                'token' => $invitacion->token,
                'url' => config('app.frontend_url') . '/invitaciones/' . $invitacion->token,
                'expira_en' => $invitacion->expira_en,
                'invitacion' => $invitacion
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}
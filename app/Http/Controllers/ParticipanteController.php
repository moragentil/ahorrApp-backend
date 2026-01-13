<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\Interface\ParticipanteServiceInterface;
use App\Services\Interface\InvitacionGrupoServiceInterface;
use App\Models\User; // <-- import

class ParticipanteController extends Controller
{
    protected $service;
    protected $invitacionService;

    public function __construct(
        ParticipanteServiceInterface $service,
        InvitacionGrupoServiceInterface $invitacionService
    ) {
        $this->service = $service;
        $this->invitacionService = $invitacionService;
    }

    public function index($grupoId)
    {
        return response()->json($this->service->all($grupoId));
    }

    public function show($id)
    {
        return response()->json($this->service->find($id));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'grupo_gasto_id' => 'required|exists:grupo_gastos,id',
            'nombre' => 'required|string|max:255',
            'email' => 'nullable|email',
            'user_id' => 'nullable|exists:users,id',
        ]);

        try {
            $participante = $this->service->create($data);

            // Enviar invitación automática si el email pertenece a un usuario registrado
            if (!empty($data['email'])) {
                $usuario = User::where('email', $data['email'])->first();
                if ($usuario) {
                    $this->invitacionService->enviarInvitacion(
                        $data['grupo_gasto_id'],
                        $data['email'],
                        $request->user()->id
                    );
                }
            }

            return response()->json($participante, 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'nombre' => 'string|max:255',
            'email' => 'nullable|email',
        ]);

        return response()->json($this->service->update($id, $data));
    }

    public function destroy($id)
    {
        $this->service->delete($id);
        return response()->json(['message' => 'Participante eliminado']);
    }

    public function vincularUsuario(Request $request, $id)
    {
        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        return response()->json($this->service->vincularUsuario($id, $data['user_id']));
    }

    public function asociarEmail(Request $request, $id)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        try {
            $participante = $this->service->asociarEmail($id, $request->email);
            
            // Enviar invitación automáticamente
            $invitacion = $this->invitacionService->enviarInvitacion(
                $participante->grupo_gasto_id,
                $request->email,
                auth()->id()
            );
            
            return response()->json([
                'participante' => $participante,
                'invitacion' => $invitacion,
                'message' => 'Email asociado e invitación enviada correctamente'
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}
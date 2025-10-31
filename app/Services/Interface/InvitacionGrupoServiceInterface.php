<?php

namespace App\Services\Interface;

interface InvitacionGrupoServiceInterface
{
    public function enviarInvitacion($grupoId, $email, $invitadoPor);
    public function misInvitaciones($userId);
    public function aceptarInvitacion($token, $userId);
    public function rechazarInvitacion($token, $userId);
    public function invitacionesPendientes($grupoId);
    public function cancelarInvitacion($invitacionId);
}
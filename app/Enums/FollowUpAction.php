<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum FollowUpAction: string implements HasLabel
{
    case CreateTask = 'create_task';
    case NotifyAgent = 'notify_agent';
    case NotifyAdmins = 'notify_admins';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::CreateTask => 'Crear una tarea para el agente',
            self::NotifyAgent => 'Notificar al agente',
            self::NotifyAdmins => 'Notificar a los administradores',
        };
    }
}

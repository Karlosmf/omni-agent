<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Customer = 'customer';
    case Finances = 'finances';
    case Sales = 'sales';
    case Staff = 'staff';
    case User = 'user';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrador',
            self::Customer => 'Cliente',
            self::Finances => 'Finanzas',
            self::Sales => 'Ventas',
            self::Staff => 'Staff',
            self::User => 'Usuario General',
        };
    }
}

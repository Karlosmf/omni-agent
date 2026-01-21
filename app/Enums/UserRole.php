<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Customer = 'customer';
    case Finance = 'finance';
    case Sales = 'sales';
    case Staff = 'staff';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrador',
            self::Customer => 'Cliente',
            self::Finance => 'Finanzas',
            self::Sales => 'Ventas',
            self::Staff => 'Staff',
        };
    }
}

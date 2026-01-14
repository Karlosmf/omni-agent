<?php

namespace App\Enums;

enum BookingStatus: string
{
    case Presupuesto = 'presupuesto';
    case Senado = 'senado';
    case Emitido = 'emitido';
}

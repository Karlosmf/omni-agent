<?php

namespace App\Enums;

enum TransactionType: string
{
    case Cobro = 'cobro';
    case Pago = 'pago';
}

<?php

namespace App\Enums;

enum ServiceType: string
{
    case Flight = 'flight';
    case Hotel = 'hotel';
    case Transfer = 'transfer';
}

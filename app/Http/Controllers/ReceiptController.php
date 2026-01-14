<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Barryvdh\DomPDF\Facade\Pdf;

class ReceiptController extends Controller
{
    public function download(Transaction $transaction)
    {
        $pdf = Pdf::loadView('pdf.receipt', ['transaction' => $transaction]);

        return $pdf->download('recibo-'.$transaction->id.'.pdf');
    }
}

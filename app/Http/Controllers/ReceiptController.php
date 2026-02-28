<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Gate;

class ReceiptController extends Controller
{
    public function download(Transaction $transaction)
    {
        Gate::authorize('view', $transaction);

        $pdf = Pdf::loadView('pdf.receipt', ['transaction' => $transaction]);

        return $pdf->download('recibo-'.$transaction->id.'.pdf');
    }
}

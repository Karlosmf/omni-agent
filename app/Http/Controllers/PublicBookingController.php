<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class PublicBookingController extends Controller
{
    /**
     * Show the public landing page for a booking proposal.
     */
    public function show(string $token, Request $request): View|Response
    {
        $booking = Booking::where('public_token', $token)
            ->with(['items.serviceType', 'items.supplier', 'transactions'])
            ->firstOrFail();

        $settings = get_agency_settings();

        if ($request->query('format') === 'pdf') {
            return response()->streamDownload(function () use ($booking, $settings) {
                echo Pdf::loadView('pdf.booking', [
                    'booking' => $booking,
                    'settings' => $settings,
                ])->output();
            }, 'presupuesto-'.$booking->file_number.'.pdf');
        }

        return view('public.booking-proposal', compact('booking', 'settings'));
    }
}

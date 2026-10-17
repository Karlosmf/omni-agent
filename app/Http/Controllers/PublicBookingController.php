<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Booking;
use App\Models\BookingActivity;
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
            ->with(['items.serviceType', 'items.supplier', 'transactions', 'installments'])
            ->firstOrFail();

        $settings = get_agency_settings();

        $this->registerView($booking, $request);

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

    /**
     * Count a customer opening the proposal. Staff previews and crawlers are ignored.
     */
    private function registerView(Booking $booking, Request $request): void
    {
        $user = $request->user();

        if (($user && $user->role !== UserRole::Customer) || preg_match('/bot|crawl|spider|preview|facebookexternalhit|whatsapp/i', (string) $request->userAgent())) {
            return;
        }

        $previous = $booking->last_viewed_at;

        $booking->forceFill([
            'first_viewed_at' => $booking->first_viewed_at ?? now(),
            'last_viewed_at' => now(),
            'view_count' => $booking->view_count + 1,
        ])->saveQuietly();

        // Keep the timeline readable: log the first view and then at most one every 30 minutes.
        if (! $previous || $previous->lt(now()->subMinutes(30))) {
            BookingActivity::create([
                'booking_id' => $booking->id,
                'type' => 'viewed',
                'description' => $previous
                    ? "El cliente volvió a abrir el presupuesto (vez {$booking->view_count})"
                    : 'El cliente abrió el presupuesto por primera vez',
            ]);
        }
    }
}

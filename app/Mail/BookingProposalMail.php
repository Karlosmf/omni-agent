<?php

namespace App\Mail;

use App\Models\AgencySetting;
use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingProposalMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @var AgencySetting|null
     */
    public $settings;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Booking $booking,
        private string $pdfContent,
    ) {
        $this->settings = get_agency_settings();
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $agencyName = $this->settings?->name ?? 'OmniAgent';
        $destination = $this->booking->destination ? " — {$this->booking->destination}" : '';

        return new Envelope(
            subject: "Tu presupuesto de viaje{$destination} — {$agencyName}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.booking.proposal',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [
            Attachment::fromData(
                fn () => $this->pdfContent,
                'presupuesto-'.$this->booking->file_number.'.pdf'
            )->withMime('application/pdf'),
        ];
    }
}

<?php

namespace App\Mail;

use App\Models\AgencySetting;
use App\Models\PaymentInstallment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InstallmentReminderMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public const STAGE_BEFORE = 'before';

    public const STAGE_DUE = 'due';

    public const STAGE_OVERDUE = 'overdue';

    public const DEFAULT_TEXTS = [
        self::STAGE_BEFORE => 'Hola [NOMBRE], te recordamos que el [VENCIMIENTO] vence la [CUOTA] de tu viaje [EXPEDIENTE] por USD [MONTO].',
        self::STAGE_DUE => 'Hola [NOMBRE], hoy vence la [CUOTA] de tu viaje [EXPEDIENTE] por USD [MONTO].',
        self::STAGE_OVERDUE => 'Hola [NOMBRE], la [CUOTA] de tu viaje [EXPEDIENTE] por USD [MONTO] venció el [VENCIMIENTO]. Si ya la abonaste, avisanos y la registramos.',
    ];

    public function __construct(public PaymentInstallment $installment, public string $stage) {}

    public function envelope(): Envelope
    {
        $agency = get_agency_settings()?->company_name ?? config('app.name');

        return new Envelope(subject: "Recordatorio de pago — {$this->installment->booking->file_number} — {$agency}");
    }

    public function content(): Content
    {
        $settings = get_agency_settings();

        return new Content(
            markdown: 'emails.installment.reminder',
            with: [
                'body' => self::bodyText($this->installment, $this->stage, $settings),
                'paymentUrl' => $this->installment->booking->publicUrl(),
                'instructions' => $settings?->payment_instructions,
                'agency' => $settings?->company_name ?? config('app.name'),
            ],
        );
    }

    /**
     * The reminder text for a stage, with the agency's own wording when it set one.
     */
    public static function bodyText(PaymentInstallment $installment, string $stage, ?AgencySetting $settings = null): string
    {
        $template = match ($stage) {
            self::STAGE_BEFORE => $settings?->reminder_before_text,
            self::STAGE_DUE => $settings?->reminder_due_text,
            default => $settings?->reminder_overdue_text,
        } ?: self::DEFAULT_TEXTS[$stage] ?? self::DEFAULT_TEXTS[self::STAGE_DUE];

        return strtr($template, [
            '[NOMBRE]' => $installment->booking->holder_name ?? 'cliente',
            '[CUOTA]' => $installment->label,
            '[MONTO]' => number_format($installment->remaining(), 2, ',', '.'),
            '[VENCIMIENTO]' => $installment->due_date->format('d/m/Y'),
            '[EXPEDIENTE]' => $installment->booking->file_number,
        ]);
    }
}

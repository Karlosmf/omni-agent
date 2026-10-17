<?php

namespace App\Console\Commands;

use App\Mail\InstallmentReminderMail;
use App\Models\PaymentInstallment;
use App\Models\WhatsAppTemplate;
use App\Services\InstallmentService;
use App\Services\WhatsApp\WhatsAppGateway;
use App\Support\PhoneNumber;
use Filament\Notifications\Notification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class InstallmentReminders extends Command
{
    protected $signature = 'omni:installment-reminders';

    protected $description = 'Marca las cuotas vencidas y avisa 3 días antes, el día del vencimiento y 2 días después.';

    public function handle(InstallmentService $installments): int
    {
        $installments->markOverdue();

        $stages = [
            InstallmentReminderMail::STAGE_BEFORE => today()->addDays(3),
            InstallmentReminderMail::STAGE_DUE => today(),
            InstallmentReminderMail::STAGE_OVERDUE => today()->subDays(2),
        ];

        $sent = 0;

        foreach ($stages as $stage => $date) {
            PaymentInstallment::query()
                ->whereIn('status', ['pending', 'partial', 'overdue'])
                ->whereDate('due_date', $date)
                ->with('booking.customer', 'booking.agent')
                ->each(function (PaymentInstallment $installment) use ($stage, &$sent) {
                    if (in_array($stage, $installment->reminders_sent ?? [], true)) {
                        return;
                    }

                    $this->notify($installment, $stage);

                    $installment->update(['reminders_sent' => [...($installment->reminders_sent ?? []), $stage]]);
                    $sent++;
                });
        }

        $this->info("Recordatorios enviados: {$sent}");

        return self::SUCCESS;
    }

    private function notify(PaymentInstallment $installment, string $stage): void
    {
        $booking = $installment->booking;

        try {
            if ($booking->customer?->email) {
                Mail::to($booking->customer->email)->send(new InstallmentReminderMail($installment, $stage));
            }

            $this->sendWhatsApp($installment, $stage);

            if ($booking->agent) {
                Notification::make()
                    ->title($stage === InstallmentReminderMail::STAGE_OVERDUE ? 'Cuota vencida' : 'Cuota por vencer')
                    ->body("{$booking->file_number} — {$installment->label}: USD ".number_format($installment->remaining(), 2).' ('.$installment->due_date->format('d/m').')')
                    ->warning()
                    ->sendToDatabase($booking->agent);
            }
        } catch (\Throwable $e) {
            Log::warning("Installment reminder failed for #{$installment->id}: ".$e->getMessage());
        }
    }

    /**
     * Also remind by WhatsApp when the agency connected it and registered an approved template.
     * Reminders go out of the 24 h window, so only templates can be used.
     */
    private function sendWhatsApp(PaymentInstallment $installment, string $stage): void
    {
        $gateway = app(WhatsAppGateway::class);
        $template = WhatsAppTemplate::forPurpose('installment_reminder');
        $customer = $installment->booking->customer;
        $phone = PhoneNumber::toE164($customer?->phone);

        if (! $gateway->isConfigured() || ! $template || ! $phone) {
            return;
        }

        try {
            $gateway->sendTemplate($phone, $template, array_slice([
                $installment->booking->holder_name ?? $customer->name,
                $installment->label,
                number_format($installment->remaining(), 2, ',', '.'),
                $installment->due_date->format('d/m/Y'),
            ], 0, $template->variables_count));
        } catch (\Throwable $e) {
            Log::warning("WhatsApp reminder failed for installment #{$installment->id}: ".$e->getMessage());
        }
    }
}

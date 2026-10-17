<?php

namespace App\Console\Commands;

use App\Mail\InstallmentReminderMail;
use App\Models\PaymentInstallment;
use App\Services\InstallmentService;
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
}

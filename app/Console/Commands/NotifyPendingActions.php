<?php

namespace App\Console\Commands;

use App\Enums\BookingStatus;
use App\Enums\UserRole;
use App\Models\Booking;
use App\Models\Task;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Console\Command;

class NotifyPendingActions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'omni:notify-pending';

    protected $description = 'Envía notificaciones de tareas pendientes y vencimientos cercanos';

    public function handle()
    {
        $this->info('Verificando tareas pendientes...');

        $tasks = Task::where('is_completed', false)
            ->whereNotNull('due_date')
            ->where('due_date', '<=', now()->toDateString())
            ->with('agent')
            ->get();

        foreach ($tasks as $task) {
            if ($task->agent) {
                Notification::make()
                    ->title('Tarea Vencida o por Vencer')
                    ->body("La tarea '{$task->title}' está pendiente.")
                    ->warning()
                    ->sendToDatabase($task->agent);
            }
        }

        $this->info('Verificando presupuestos por vencer...');

        $bookings = Booking::whereIn('status', [
            BookingStatus::Borrador,
            BookingStatus::Presupuesto,
        ])
            ->whereNotNull('valid_until')
            ->where('valid_until', '<=', now()->addDays(2)->toDateString())
            ->with('agent')
            ->get();

        foreach ($bookings as $booking) {
            $user = $booking->agent ?? User::where('role', UserRole::Admin)->first();
            if ($user) {
                Notification::make()
                    ->title('Presupuesto por vencer')
                    ->body("El presupuesto #{$booking->file_number} ({$booking->holder_name}) vence pronto.")
                    ->warning()
                    ->sendToDatabase($user);
            }
        }

        $this->info('Notificaciones enviadas.');
    }
}

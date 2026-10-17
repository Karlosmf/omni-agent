<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\InstallmentStatus;
use App\Enums\TransactionType;
use App\Models\Booking;
use App\Models\BookingActivity;
use App\Models\PaymentInstallment;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Payment plan of a booking: deposit + installments, always in USD like the booking.
 */
class InstallmentService
{
    public const FREQUENCIES = ['weekly' => 'Semanal', 'biweekly' => 'Quincenal', 'monthly' => 'Mensual'];

    /**
     * Build the schedule: the deposit (due today) plus $installments equal parts of the rest.
     * Rounding differences go to the last installment.
     *
     * @throws InvalidArgumentException When the plan cannot be built or would end too close to the trip.
     */
    public function generate(
        Booking $booking,
        float $depositPercent,
        int $installments,
        CarbonInterface $firstDueDate,
        string $frequency = 'monthly',
    ): void {
        $total = round((float) $booking->total_sell, 2);

        if ($total <= 0) {
            throw new InvalidArgumentException('El expediente no tiene un total de venta para financiar.');
        }

        if ($depositPercent < 0 || $depositPercent > 100 || $installments < 0 || ! isset(self::FREQUENCIES[$frequency])) {
            throw new InvalidArgumentException('Los datos del plan de pagos no son válidos.');
        }

        if ($booking->installments()->where('paid_amount', '>', 0)->exists()) {
            throw new InvalidArgumentException('El plan ya tiene pagos registrados: no se puede regenerar.');
        }

        $deposit = round($total * $depositPercent / 100, 2);
        $rest = round($total - $deposit, 2);
        $rows = [];

        if ($deposit > 0) {
            $rows[] = ['number' => 0, 'label' => 'Seña', 'due_date' => today(), 'amount' => $deposit];
        }

        if ($rest > 0) {
            $count = max(1, $installments);
            $part = round($rest / $count, 2);
            $due = Carbon::instance($firstDueDate)->startOfDay();

            for ($i = 1; $i <= $count; $i++) {
                $rows[] = [
                    'number' => $i,
                    'label' => $count === 1 ? 'Saldo' : "Cuota {$i}/{$count}",
                    'due_date' => $due->copy(),
                    'amount' => $i === $count ? round($rest - $part * ($count - 1), 2) : $part,
                ];
                $due = $this->advance($due, $frequency);
            }
        }

        $this->assertEndsBeforeTrip($booking, $rows);

        DB::transaction(function () use ($booking, $rows) {
            $booking->installments()->delete();
            $booking->installments()->createMany($rows);

            BookingActivity::log($booking, 'payment_plan', 'Plan de pagos generado ('.count($rows).' cuotas)');
        });

        $this->sync($booking);
    }

    /**
     * Recompute what each installment has collected from the booking's cobros, then update statuses.
     *
     * Cobros assigned to an installment count towards it first; whatever exceeds it, plus the cobros with
     * no installment, pays the oldest open installments first. Safe to run any number of times.
     */
    public function sync(Booking $booking): void
    {
        $installments = $booking->installments()->get();

        if ($installments->isEmpty()) {
            return;
        }

        $open = $installments->where('status', '!=', InstallmentStatus::Cancelled);
        $openIds = $open->pluck('id');

        $cobros = $booking->transactions()->where('type', TransactionType::Cobro)->get(['id', 'installment_id', 'amount_usd_fixed']);
        $assigned = $cobros->whereIn('installment_id', $openIds)->groupBy('installment_id')->map(fn ($group) => round((float) $group->sum('amount_usd_fixed'), 2));
        // Cobros with no installment, or pointing at a cancelled one, pay the oldest open installments.
        $pool = round((float) $cobros->whereNotIn('installment_id', $openIds)->sum('amount_usd_fixed'), 2);

        $paid = [];
        foreach ($open as $installment) {
            $own = (float) ($assigned[$installment->id] ?? 0);
            $applied = min((float) $installment->amount, $own);
            $paid[$installment->id] = $applied;
            $pool += $own - $applied;
        }

        foreach ($open as $installment) {
            $room = (float) $installment->amount - $paid[$installment->id];
            $take = min($room, max(0, $pool));
            $paid[$installment->id] += $take;
            $pool -= $take;
        }

        foreach ($open as $installment) {
            $installment->update([
                'paid_amount' => round($paid[$installment->id], 2),
                'status' => $this->statusFor($installment, round($paid[$installment->id], 2)),
            ]);
        }

        $this->markBookingAsDepositedIfNeeded($booking, $installments->fresh());
    }

    /**
     * Flag the open installments whose due date has passed. Returns how many changed.
     */
    public function markOverdue(): int
    {
        return PaymentInstallment::whereIn('status', [InstallmentStatus::Pending, InstallmentStatus::Partial])
            ->whereDate('due_date', '<', today())
            ->update(['status' => InstallmentStatus::Overdue]);
    }

    private function statusFor(PaymentInstallment $installment, float $paid): InstallmentStatus
    {
        if ($paid + 0.005 >= (float) $installment->amount) {
            return InstallmentStatus::Paid;
        }

        if ($installment->due_date->lt(today())) {
            return InstallmentStatus::Overdue;
        }

        return $paid > 0 ? InstallmentStatus::Partial : InstallmentStatus::Pending;
    }

    /**
     * Once the deposit (first installment) is paid, a quote becomes a reserved booking.
     */
    private function markBookingAsDepositedIfNeeded(Booking $booking, $installments): void
    {
        $first = $installments->where('status', '!=', InstallmentStatus::Cancelled)->sortBy('number')->first();

        if ($first?->status === InstallmentStatus::Paid
            && in_array($booking->status, [BookingStatus::Borrador, BookingStatus::Presupuesto], true)) {
            $booking->update(['status' => BookingStatus::Senado]);
        }
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function assertEndsBeforeTrip(Booking $booking, array $rows): void
    {
        if (! $booking->travel_date || $rows === []) {
            return;
        }

        $days = (int) (get_agency_settings()?->installments_last_due_days_before_trip ?? 30);
        $limit = Carbon::parse($booking->travel_date)->startOfDay()->subDays($days);
        $last = Carbon::parse(collect($rows)->max('due_date'));

        if ($last->gt($limit)) {
            throw new InvalidArgumentException(
                "La última cuota vence el {$last->format('d/m/Y')}, pero debe quedar saldada {$days} días antes del viaje (hasta el {$limit->format('d/m/Y')})."
            );
        }
    }

    private function advance(Carbon $date, string $frequency): Carbon
    {
        return match ($frequency) {
            'weekly' => $date->copy()->addWeek(),
            'biweekly' => $date->copy()->addDays(15),
            default => $date->copy()->addMonthNoOverflow(),
        };
    }
}
